<?php
namespace EWW\Dpf\Common;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use EWW\Dpf\Services\MetsService;

/**
 * dpf-native METS document for the landing-page plugins.
 *
 * Replaces \Kitodo\Dlf\Common\Document / MetsDocument. Fetches METS
 * directly from Redis/Fedora — no HTTP self-loop. Ported from dpf main
 * (EWW\Dpf\Common\MetsDocument) with adaptations for dpf-3x:
 * - getInstance() uses MetsService instead of Guzzle
 * - getTitleData() uses hardcoded MODS XPaths instead of tx_dpf_metadata
 *
 * API surface exposed to plugins: $mets, $recordId, $toplevelId, $uid,
 * $ready, $cPid and getTitleData().
 */
class MetsDocument
{
    const NAMESPACES = [
        'mets' => 'http://www.loc.gov/METS/',
        'mods' => 'http://www.loc.gov/mods/v3',
        'slub' => 'http://slub-dresden.de/',
        'xlink' => 'http://www.w3.org/1999/xlink',
        'xml' => 'http://www.w3.org/XML/1998/namespace',
    ];

    /**
     * Supported dmdSec metadata formats: MDTYPE/OTHERMDTYPE => root element.
     */
    const FORMATS = [
        'MODS' => 'mods',
        'SLUB' => 'info',
    ];

    /** @var bool */
    public $ready = false;

    /**
     * Document location (METS URL). DLF exposed this as $uid for URL-loaded
     * documents; plugins pass it through to log messages only.
     *
     * @var string
     */
    public $uid = '';

    /**
     * Configuration PID (cPid). Accepted by getTitleData() for DLF parity
     * but not used — dpf-3x extracts metadata via hardcoded MODS XPaths.
     *
     * @var int
     */
    public $cPid = 0;

    /** @var string */
    public $recordId = '';

    /** @var \SimpleXMLElement|null */
    public $mets = null;

    /** @var string */
    public $toplevelId = '';

    /**
     * dmdSec ID => ['type' => 'MODS'|'SLUB', 'xml' => \SimpleXMLElement]
     *
     * @var array|null
     */
    protected $dmdSec = null;

    /**
     * Per-request instance registry keyed by md5($location).
     * All five landing-page plugins share one fetch per page load.
     *
     * @var array
     */
    protected static $registry = [];

    /**
     * Return a MetsDocument for the given METS URL, or null on failure.
     *
     * Extracts the Fedora PID from the URL path (/api/{pid}/mets/) and
     * fetches XML via MetsService (Redis → Fedora), bypassing HTTP.
     *
     * @param string $location
     * @return MetsDocument|null
     */
    public static function getInstance(string $location)
    {
        $key = md5($location);
        if (array_key_exists($key, self::$registry)) {
            return self::$registry[$key];
        }

        $instance = null;
        $path = (string) parse_url($location, PHP_URL_PATH);
        if (preg_match('#/api/([^/]+)/mets/?#', $path, $m)) {
            $pid = urldecode($m[1]);
            // Validate Fedora PID format before use — prevents SSRF via crafted tx_dlf[id]
            if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*:[A-Za-z0-9._-]+$/', $pid)) {
                self::$registry[$key] = null;
                return null;
            }
            $settings = MetsService::readSettings();
            $xml = (new MetsService($settings))->getXml($pid);
            if ($xml !== null) {
                $instance = self::fromXmlString($xml, $location);
            }
        }

        self::$registry[$key] = $instance;
        return $instance;
    }

    /**
     * Build a MetsDocument from a METS XML string (also used in tests).
     *
     * @param string $xml
     * @param string $location
     * @return MetsDocument
     */
    public static function fromXmlString(string $xml, string $location = ''): MetsDocument
    {
        $document = new self();
        $document->uid = $location;

        $useInternalErrors = libxml_use_internal_errors(true);
        $simpleXml = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);

        if ($simpleXml === false) {
            return $document;
        }

        self::registerNamespaces($simpleXml);
        $mets = $simpleXml->xpath('//mets:mets');
        if (empty($mets)) {
            return $document;
        }

        $document->mets = $mets[0];
        self::registerNamespaces($document->mets);
        $document->recordId = (string) $document->mets['OBJID'];
        $document->ready = true;
        $document->resolveToplevelId();

        return $document;
    }

    /**
     * Register the known namespaces on a SimpleXMLElement or DOMXPath.
     *
     * @param \SimpleXMLElement|\DOMXPath $obj
     * @return void
     */
    public static function registerNamespaces($obj)
    {
        foreach (self::NAMESPACES as $prefix => $namespace) {
            if ($obj instanceof \DOMXPath) {
                $obj->registerNamespace($prefix, $namespace);
            } else {
                $obj->registerXPathNamespace($prefix, $namespace);
            }
        }
    }

    /**
     * Resolve the toplevel logical structure node's @ID.
     *
     * @return void
     */
    protected function resolveToplevelId()
    {
        $divs = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@DMDID and not(./mets:mptr)]');
        if (!empty($divs)) {
            $logicalToPhysical = $this->getSmLinks();
            foreach ($divs as $div) {
                $id = (string) $div['ID'];
                if (array_key_exists($id, $logicalToPhysical)) {
                    $this->toplevelId = $id;
                    break;
                }
                if (empty($this->toplevelId)) {
                    $this->toplevelId = $id;
                }
            }
        }
    }

    /** @return string */
    public function getToplevelId(): string
    {
        return $this->toplevelId;
    }

    /**
     * structLink map: logical @ID => list of physical @IDs.
     *
     * @return array
     */
    protected function getSmLinks(): array
    {
        $links = [];
        $smLinks = $this->mets->xpath('./mets:structLink/mets:smLink');
        if (!empty($smLinks)) {
            foreach ($smLinks as $smLink) {
                $from = (string) $smLink->attributes('http://www.w3.org/1999/xlink')->from;
                $to = (string) $smLink->attributes('http://www.w3.org/1999/xlink')->to;
                $links[$from][] = $to;
            }
        }
        return $links;
    }

    /**
     * dmdSec map: ID => ['type' => format type, 'xml' => content root element].
     *
     * @return array
     */
    public function getDmdSec(): array
    {
        if ($this->dmdSec !== null) {
            return $this->dmdSec;
        }
        $this->dmdSec = [];
        $dmdIds = $this->mets->xpath('./mets:dmdSec/@ID');
        if (!empty($dmdIds)) {
            foreach ($dmdIds as $dmdId) {
                $id = (string) $dmdId;
                $xml = null;
                $type = $this->mets->xpath('./mets:dmdSec[@ID="' . $id . '"]/mets:mdWrap[not(@MDTYPE="OTHER")]/@MDTYPE');
                if (!empty($type) && isset(self::FORMATS[(string) $type[0]])) {
                    $type = (string) $type[0];
                    $xml = $this->mets->xpath(
                        './mets:dmdSec[@ID="' . $id . '"]/mets:mdWrap[@MDTYPE="' . $type . '"]/mets:xmlData/'
                        . strtolower($type) . ':' . self::FORMATS[$type]
                    );
                } else {
                    $type = $this->mets->xpath('./mets:dmdSec[@ID="' . $id . '"]/mets:mdWrap[@MDTYPE="OTHER"]/@OTHERMDTYPE');
                    if (!empty($type) && isset(self::FORMATS[(string) $type[0]])) {
                        $type = (string) $type[0];
                        $xml = $this->mets->xpath(
                            './mets:dmdSec[@ID="' . $id . '"]/mets:mdWrap[@MDTYPE="OTHER"][@OTHERMDTYPE="' . $type . '"]/mets:xmlData/'
                            . strtolower($type) . ':' . self::FORMATS[$type]
                        );
                    }
                }
                if (!empty($xml)) {
                    self::registerNamespaces($xml[0]);
                    $this->dmdSec[$id] = ['type' => $type, 'xml' => $xml[0]];
                }
            }
        }
        return $this->dmdSec;
    }

    /**
     * ORDER/LABEL/ORDERLABEL attributes of the toplevel logical div.
     *
     * @return array ['order' => string, 'label' => string, 'orderlabel' => string]
     */
    public function getToplevelStructureInfo(): array
    {
        $info = ['order' => '', 'label' => '', 'orderlabel' => ''];
        $toplevelId = $this->getToplevelId();
        if (!empty($toplevelId)) {
            $divs = $this->mets->xpath('./mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="' . $toplevelId . '"]');
            if (!empty($divs)) {
                $info['order'] = (string) $divs[0]['ORDER'];
                $info['label'] = (string) $divs[0]['LABEL'];
                $info['orderlabel'] = (string) $divs[0]['ORDERLABEL'];
            }
        }
        return $info;
    }

    /**
     * Replicates DLF Document::getTitledata() for the fields consumed by
     * Coins and MetaTags. Uses hardcoded MODS XPaths — no tx_dlf_metadata
     * query. The $cPid parameter is accepted for call-site compatibility only.
     *
     * @param int $cPid Ignored (DLF parity).
     * @return array
     */
    public function getTitleData($cPid = 0): array
    {
        $result = $this->extractModsMetadata();

        if (!empty($result)) {
            $structureInfo = $this->getToplevelStructureInfo();
            $result['mets_order'] = [$structureInfo['order']];
            $result['mets_label'] = [$structureInfo['label']];
            $result['mets_orderlabel'] = [$structureInfo['orderlabel']];

            if (!empty($this->recordId) && !in_array($this->recordId, $result['record_id'] ?? [])) {
                array_unshift($result['record_id'], $this->recordId);
            }
        }

        return $result;
    }

    /**
     * Extract metadata from the MODS dmdSec linked to the toplevel logical div.
     *
     * Returns key => [values] pairs matching the index_name conventions used
     * by Coins (OpenURL) and MetaTags (citation_* meta tags).
     *
     * @return array
     */
    protected function extractModsMetadata(): array
    {
        $mods = $this->getTopLevelModsSec();
        if ($mods === null) {
            return [];
        }

        $result = [];

        // record_id
        if (!empty($this->recordId)) {
            $result['record_id'] = [$this->recordId];
        }

        // title
        $this->xpathInto($result, 'title', $mods, 'mods:titleInfo[not(@type)]/mods:title');

        // identifiers (root document)
        $this->xpathInto($result, 'urn', $mods, 'mods:identifier[@type="urn"]');
        $this->xpathInto($result, 'doi', $mods, 'mods:identifier[@type="doi"]');
        $this->xpathInto($result, 'isbn', $mods, 'mods:identifier[@type="isbn"]');
        $this->xpathInto($result, 'issn', $mods, 'mods:identifier[@type="issn"]');

        // language
        $this->xpathInto($result, 'language', $mods, 'mods:language/mods:languageTerm');

        // date (dateissued / publication_date both drawn from mods:dateIssued)
        $dateNodes = $mods->xpath('mods:originInfo/mods:dateIssued');
        if (!empty($dateNodes)) {
            $result['dateissued'] = [(string) $dateNodes[0]];
            $result['publication_date'] = [(string) $dateNodes[0]];
        }

        // place of publication
        $this->xpathInto($result, 'place', $mods, 'mods:originInfo/mods:place/mods:placeTerm[@type="text"]');

        // abstracts (xml:lang attribute)
        $this->xpathIntoLang($result, 'abstract_ger', $mods, 'mods:abstract', 'de');
        $this->xpathIntoLang($result, 'abstract_eng', $mods, 'mods:abstract', 'en');

        // personal names → author1, author2, ...
        $authorIdx = 1;
        $nameNodes = $mods->xpath('mods:name[@type="personal"]');
        foreach ($nameNodes as $nameNode) {
            self::registerNamespaces($nameNode);
            $family = $nameNode->xpath('mods:namePart[@type="family"]');
            $given = $nameNode->xpath('mods:namePart[@type="given"]');
            $plain = $nameNode->xpath('mods:namePart[not(@type)]');

            if (!empty($family)) {
                $name = (string) $family[0];
                if (!empty($given)) {
                    $name .= ', ' . (string) $given[0];
                }
            } elseif (!empty($plain)) {
                $name = (string) $plain[0];
            } else {
                continue;
            }

            $result['author' . $authorIdx] = [$name];
            $authorIdx++;
        }

        // publisher names → publisher1, publisher2, ...
        $pubNodes = $mods->xpath('mods:originInfo/mods:publisher');
        $pubIdx = 1;
        foreach ($pubNodes as $pub) {
            $result['publisher' . $pubIdx] = [(string) $pub];
            $pubIdx++;
        }

        // corporate publisher
        $corpNodes = $mods->xpath('mods:name[@type="corporate"]/mods:namePart');
        if (!empty($corpNodes)) {
            $result['original_corporation_publisher'] = [(string) $corpNodes[0]];
        }

        // volume / issue from root part elements
        $this->xpathInto($result, 'volume', $mods, 'mods:part[@type="volume"]/mods:detail/mods:number');
        $this->xpathInto($result, 'issue', $mods, 'mods:part[@type="issue"]/mods:detail/mods:number');

        // host relatedItem (journal metadata for articles)
        $hostNodes = $mods->xpath('mods:relatedItem[@type="host"]');
        if (!empty($hostNodes)) {
            $host = $hostNodes[0];
            self::registerNamespaces($host);

            $this->xpathInto($result, 'original_title', $host, 'mods:titleInfo[not(@type)]/mods:title');
            $this->xpathInto($result, 'original_subtitle', $host, 'mods:titleInfo[@type="abbreviated"]/mods:title');
            $this->xpathInto($result, 'original_issn', $host, 'mods:identifier[@type="issn"]');
            $this->xpathInto($result, 'original_isbn', $host, 'mods:identifier[@type="isbn"]');
            $this->xpathInto($result, 'original_urn', $host, 'mods:identifier[@type="urn"]');
            $this->xpathInto($result, 'original_doi', $host, 'mods:identifier[@type="doi"]');
            $this->xpathInto($result, 'original_place', $host, 'mods:originInfo/mods:place/mods:placeTerm[@type="text"]');
            $this->xpathInto($result, 'original_volume', $host, 'mods:part[@type="volume"]/mods:detail/mods:number');
            $this->xpathInto($result, 'original_issue', $host, 'mods:part[@type="issue"]/mods:detail/mods:number');
            $this->xpathInto($result, 'original_pages', $host, 'mods:part/mods:extent[@unit="pages"]/mods:start');
            $this->xpathInto($result, 'original_pages2', $host, 'mods:part/mods:extent[@unit="pages"]/mods:end');
        }

        // pages at root level (alternative position per Qucosa MODS conventions)
        if (!isset($result['original_pages'])) {
            $this->xpathInto($result, 'original_pages', $mods, 'mods:part[@type="section"]/mods:extent[@unit="pages"]/mods:start');
        }
        if (!isset($result['original_pages2'])) {
            $this->xpathInto($result, 'original_pages2', $mods, 'mods:part[@type="section"]/mods:extent[@unit="pages"]/mods:end');
        }

        // series relatedItem → series_urn
        $seriesNodes = $mods->xpath('mods:relatedItem[@type="series"]');
        if (!empty($seriesNodes)) {
            self::registerNamespaces($seriesNodes[0]);
            $this->xpathInto($result, 'series_urn', $seriesNodes[0], 'mods:identifier[@type="urn"]');
        }

        return $result;
    }

    /**
     * Find the MODS SimpleXMLElement linked to the toplevel logical div.
     *
     * @return \SimpleXMLElement|null
     */
    protected function getTopLevelModsSec()
    {
        $dmdSecs = $this->getDmdSec();

        // Prefer the dmdSec referenced by the toplevel logical div
        if (!empty($this->toplevelId)) {
            $divs = $this->mets->xpath(
                './mets:structMap[@TYPE="LOGICAL"]//mets:div[@ID="' . $this->toplevelId . '"]'
            );
            if (!empty($divs) && isset($divs[0]['DMDID'])) {
                $dmdId = (string) $divs[0]['DMDID'];
                if (isset($dmdSecs[$dmdId]) && $dmdSecs[$dmdId]['type'] === 'MODS') {
                    return $dmdSecs[$dmdId]['xml'];
                }
            }
        }

        // Fallback: first MODS dmdSec
        foreach ($dmdSecs as $dmdSec) {
            if ($dmdSec['type'] === 'MODS') {
                return $dmdSec['xml'];
            }
        }

        return null;
    }

    /**
     * Run an XPath on $node; if non-empty store first value under $key in $result.
     *
     * @param array $result
     * @param string $key
     * @param \SimpleXMLElement $node
     * @param string $xpath
     * @return void
     */
    private function xpathInto(array &$result, string $key, \SimpleXMLElement $node, string $xpath)
    {
        $nodes = $node->xpath($xpath);
        if (!empty($nodes)) {
            $result[$key] = [(string) $nodes[0]];
        }
    }

    /**
     * Run an XPath on $node filtered by xml:lang; store first match under $key.
     *
     * @param array $result
     * @param string $key
     * @param \SimpleXMLElement $node
     * @param string $xpath Element XPath without lang filter
     * @param string $lang Language code, e.g. "de" or "en"
     * @return void
     */
    private function xpathIntoLang(array &$result, string $key, \SimpleXMLElement $node, string $xpath, string $lang)
    {
        // Try xml:lang attribute (correct XML namespace)
        $nodes = $node->xpath($xpath . '[@xml:lang="' . $lang . '"]');
        if (empty($nodes)) {
            // Fallback: plain @lang attribute (non-standard but used in some Qucosa records)
            $nodes = $node->xpath($xpath . '[@lang="' . $lang . '"]');
        }
        if (!empty($nodes)) {
            $result[$key] = [(string) $nodes[0]];
        }
    }
}
