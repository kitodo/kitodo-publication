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

use EWW\Dpf\Services\Metadata\MetadataExtractor;
use EWW\Dpf\Services\Metadata\MetadataMappingRepository;

/**
 * dpf-native METS document for the landing-page plugins.
 *
 * Replaces \Kitodo\Dlf\Common\Document / MetsDocument for the narrow API
 * surface the dpf plugins consume: $mets, $recordId, $toplevelId, $uid,
 * $ready, $cPid and getTitleData().
 *
 * Deviation from DLF: the metadata format registry (tx_dlf_formats) is
 * hardcoded to MODS and SLUB — the only formats occurring in Qucosa data.
 */
class MetsDocument
{
    const NAMESPACES = [
        'mets' => 'http://www.loc.gov/METS/',
        'mods' => 'http://www.loc.gov/mods/v3',
        'slub' => 'http://slub-dresden.de/',
        'xlink' => 'http://www.w3.org/1999/xlink',
    ];

    /**
     * Supported dmdSec metadata formats: MDTYPE/OTHERMDTYPE => root element.
     */
    const FORMATS = [
        'MODS' => 'mods',
        'SLUB' => 'info',
    ];

    /**
     * @var bool
     */
    public $ready = false;

    /**
     * The document location (METS URL) — DLF exposed this as $uid for
     * URL-loaded documents, the plugins only pass it through to log messages.
     *
     * @var string
     */
    public $uid = '';

    /**
     * Configuration PID for the metadata definitions (tx_dpf_metadata).
     *
     * @var int
     */
    public $cPid = 0;

    /**
     * @var string
     */
    public $recordId = '';

    /**
     * @var \SimpleXMLElement|null
     */
    public $mets = null;

    /**
     * @var string
     */
    public $toplevelId = '';

    /**
     * dmdSec ID => ['type' => 'MODS'|'SLUB', 'xml' => \SimpleXMLElement]
     *
     * @var array|null
     */
    protected $dmdSec = null;

    /**
     * Per-request instance registry, keyed by document location.
     *
     * @var array
     */
    protected static $registry = [];

    /**
     * Get a document instance for the given METS URL.
     *
     * Memoized per request: all five landing-page plugins load the same
     * document, the METS must be fetched only once.
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
        try {
            $response = (new \GuzzleHttp\Client())->get($location, ['timeout' => 90]);
            if ($response->getStatusCode() === 200) {
                $instance = self::fromXmlString((string) $response->getBody(), $location);
            }
        } catch (\Throwable $exception) {
            $instance = null;
        }

        self::$registry[$key] = $instance;
        return $instance;
    }

    /**
     * Build a document from a METS XML string (test seam, no HTTP).
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
     * Register the known namespaces on a SimpleXML or DOMXPath object.
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
     * Same resolution as DLF MetsDocument::_getToplevelId(): logical divs
     * with @DMDID and no mptr child, preferring the first one that appears
     * in the structLink l2p map.
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

    /**
     * @return string
     */
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
     * Same resolution as DLF MetsDocument::_getDmdSec(), restricted to the
     * hardcoded FORMATS registry.
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
     * ORDER/LABEL/ORDERLABEL attributes of the toplevel logical div, for
     * DLF addMetadataFromMets() parity.
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
     * Extract all metadata for the toplevel logical structure node.
     *
     * Replicates DLF Document::getTitledata() — PHP method names are
     * case-insensitive, so existing getTitleData()/getTitledata() call
     * sites both resolve here.
     *
     * @param int $cPid The PID of the tx_dpf_metadata definitions
     * @return array
     */
    public function getTitleData($cPid = 0): array
    {
        $cPid = max((int) $cPid, 0);
        if (!$cPid && $this->cPid) {
            $cPid = $this->cPid;
        }

        $extractor = new MetadataExtractor(new MetadataMappingRepository());
        $titledata = $extractor->extract($this, $cPid);

        if (!empty($titledata)) {
            $structureInfo = $this->getToplevelStructureInfo();
            $titledata['mets_order'][0] = $structureInfo['order'];
            $titledata['mets_label'][0] = $structureInfo['label'];
            $titledata['mets_orderlabel'][0] = $structureInfo['orderlabel'];

            if (
                array_key_exists('record_id', $titledata)
                && !empty($this->recordId)
                && !in_array($this->recordId, $titledata['record_id'])
            ) {
                array_unshift($titledata['record_id'], $this->recordId);
            }
        }
        return $titledata;
    }
}
