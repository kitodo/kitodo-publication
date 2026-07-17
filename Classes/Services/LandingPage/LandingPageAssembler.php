<?php
declare(strict_types=1);
namespace EWW\Dpf\Services\LandingPage;

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

use EWW\Dpf\Common\MetsDocument;
use EWW\Dpf\Services\Metadata\MetadataMappingRepository;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Extracts and renders all data needed for the landing page.
 *
 * Each method mirrors one of the five legacy PI plugins
 * (dpf_metadata, dpf_downloadtool, dpf_relatedlisttool, dpf_coins,
 * dpf_metatags) but operates on a pre-loaded MetsDocument instead of
 * loading it per-plugin. Call getTitleData() once in the controller and
 * pass the result to the methods that need it.
 */
class LandingPageAssembler
{
    /**
     * Render the metadata <dl> block — mirrors Metadata::printMetadata().
     *
     * The tx_dpf_metadata wrap TS (key./value./all.) is applied via
     * ContentObjectRenderer::stdWrap(). showFull is always true (matching
     * the FlexForm setting on the original plugin record).
     *
     * @param MetsDocument $doc
     * @param array $metadata return value of MetsDocument::getTitleData()
     * @param array $settings Extbase settings from plugin.tx_dpf_landingpage.settings.*
     * @return string HTML string including the outer <div><dl> wrapper
     */
    public function getMetadataHtml(MetsDocument $doc, array $metadata, array $settings): string
    {
        $cPid       = (int)($settings['pages'] ?? 0);
        $sysLangUid = (int)($GLOBALS['TSFE']->sys_language_uid ?? 0);
        $separator  = $settings['separator'] ?? ', ';

        /** @var ContentObjectRenderer $cObj */
        $cObj       = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $repository = new MetadataMappingRepository();
        $fields     = $repository->findRenderableFields($cPid, $sysLangUid);

        // Build ordered field list — showFull: include all fields
        $metaList = [];
        foreach ($fields as $resArray) {
            if (
                $resArray['sys_language_uid'] != $GLOBALS['TSFE']->sys_language_content
                && $GLOBALS['TSFE']->sys_language_contentOL
            ) {
                $resArray = $GLOBALS['TSFE']->sys_page->getRecordOverlay(
                    'tx_dpf_metadata',
                    $resArray,
                    $GLOBALS['TSFE']->sys_language_content,
                    $GLOBALS['TSFE']->sys_language_contentOL
                );
            }
            if ($resArray) {
                $metaList[$resArray['index_name']] = [
                    'wrap'  => $resArray['wrap'],
                    'label' => $resArray['label'] ?: $resArray['index_name'],
                ];
            }
        }

        // Load metadata values into cObj data for stdWrap field references
        foreach ($metadata as $indexName => $value) {
            $cObj->data[$indexName] = is_array($value) ? implode($separator, $value) : $value;
        }

        // Work on a copy so array_shift does not mutate the caller's array
        $local = $metadata;

        $inner = '';
        foreach ($metaList as $indexName => $metaConf) {
            $parsedValue = '';
            $fieldwrap   = $this->parseTS($metaConf['wrap']);

            do {
                // Mirrors PI plugin: @array_shift on potentially non-array key (e.g. 'authors'
                // has no XPath rule; value comes from cObj->data via value.override.insertData)
                $value = is_array($local[$indexName] ?? null) ? array_shift($local[$indexName]) : null;
                if ($indexName === 'title') {
                    $value = !empty($value) ? htmlspecialchars((string)$value) : '';
                } elseif (in_array($indexName, ['owner', 'type', 'collection', 'language'], true) && !empty($value)) {
                    $value = htmlspecialchars($this->translateValue($indexName, (string)$value, $settings));
                } elseif (!empty($value)) {
                    $value = htmlspecialchars((string)$value);
                }
                $value = $cObj->stdWrap($value ?? '', $fieldwrap['value.'] ?? []);
                if (!empty($value)) {
                    $parsedValue .= $value;
                }
            } while (!empty($local[$indexName]));

            if (!empty($parsedValue)) {
                $field  = $cObj->stdWrap(htmlspecialchars($metaConf['label']), $fieldwrap['key.'] ?? []);
                $field .= $parsedValue;
                $inner .= $cObj->stdWrap($field, $fieldwrap['all.'] ?? []);
            }
        }

        return '<div class="tx-dpf-metadata tx-dlf-metadata"><div><dl>' . $inner . '</dl></div></div>';
    }

    /**
     * Return structured download list — mirrors DownloadTool::getAttachments().
     *
     * @param MetsDocument $doc
     * @param array $settings
     * @return array [['label' => string, 'url' => string], ...]
     */
    public function getDownloads(MetsDocument $doc, array $settings): array
    {
        $fileGrp = $settings['fileGrpDownload'] ?? '';
        if (empty($fileGrp)) {
            return [];
        }

        $attachments = $this->getRawAttachments($doc, $fileGrp);
        if (empty($attachments)) {
            return [];
        }

        $cObj    = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $apiPid  = (int)($settings['apiPid'] ?? 0);
        $result  = [];

        foreach ($attachments as $file) {
            $url = $cObj->typoLink_URL([
                'useCacheHash'     => 0,
                'parameter'        => $apiPid . ' - piwik_download',
                'additionalParams' => '&tx_dpf_getfile[qid]=' . rawurlencode((string)$doc->recordId)
                    . '&tx_dpf_getfile[action]=attachment'
                    . '&tx_dpf_getfile[attachment]=' . $file['ID'],
                'forceAbsoluteUrl' => true,
            ]);
            $result[] = [
                'label' => !empty($file['LABEL']) ? (string)$file['LABEL'] : (string)$file['ID'],
                'url'   => $url,
            ];
        }

        return $result;
    }



    /**
     * Return structured related items — mirrors RelatedListTool::getRelatedItems().
     *
     * Covers both directions: "constituent" (downward, issue→articles) and
     * "host"/"series" (upward, article→issue, issue→journal, book→series).
     *
     * @param MetsDocument $doc
     * @param array $settings
     * @return array [['title' => string, 'url' => string|null, 'type' => string], ...]
     */
    public function getRelatedItems(MetsDocument $doc, array $settings): array
    {
        $items = $doc->mets->xpath('//mods:relatedItem[@type="constituent" or @type="host" or @type="series"]');
        if (!is_array($items) || empty($items)) {
            return [];
        }

        $cObj        = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $landingPage = (int)($settings['landingPage'] ?? ($GLOBALS['TSFE']->page['uid'] ?? 0));
        $raw         = [];

        foreach ($items as $node) {
            $node->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');
            $node->registerXPathNamespace('slub', 'http://slub-dresden.de/');

            $type    = $this->preferredIdentifierType($node);
            $title   = $this->firstXPathValue($node, 'mods:titleInfo/mods:title');
            $docId   = $type !== '' ? $this->firstXPathValue($node, 'mods:identifier[@type="' . $type . '"]') : '';
            $order   = $this->firstXPathValue($node, 'mods:extension/slub:info/slub:sortingKey');
            $volume  = $this->firstXPathValue($node, 'mods:part[@type="volume" or @type="issue"]/mods:detail/mods:number');

            $raw[] = [
                'type'   => $type,
                'title'  => $title,
                'docId'  => $docId,
                'order'  => $order ?: null,
                'volume' => $volume ?: null,
            ];
        }

        // De-dupe by identifier: source MODS can carry multiple relatedItem[host]/[series]
        // blocks for the same target (migration artifact, see qucosa-15470); keep the
        // richest (non-empty title) entry per docId.
        $byDocId = [];
        foreach ($raw as $item) {
            if ($item['docId'] === '') {
                $byDocId[] = $item;
                continue;
            }
            $key = $item['type'] . '|' . $item['docId'];
            if (!isset($byDocId[$key]) || (empty($byDocId[$key]['title']) && !empty($item['title']))) {
                $byDocId[$key] = $item;
            }
        }
        $raw = array_values($byDocId);

        usort($raw, static function (array $a, array $b): int {
            return strnatcmp(
                implode(' ', [$a['order'], $a['volume'], $a['title']]),
                implode(' ', [$b['order'], $b['volume'], $b['title']])
            );
        });

        $result = [];
        foreach ($raw as $item) {
            if ($item['type'] === 'local') {
                $url = $cObj->typoLink_URL([
                    'useCacheHash'     => 1,
                    'parameter'        => $landingPage,
                    'additionalParams' => '&tx_dpf_landingpage[qid]=' . rawurlencode(strtolower($item['docId'])),
                    'forceAbsoluteUrl' => true,
                ]);
            } elseif ($item['type'] === 'urn') {
                $url = $cObj->typoLink_URL([
                    'useCacheHash'     => 0,
                    'parameter'        => 'https://nbn-resolving.de/' . $item['docId'],
                    'forceAbsoluteUrl' => true,
                ]);
            } else {
                $url = null;
            }

            $label = $item['title'] ?: $item['docId'];
            if ($item['order']) {
                $label .= ' - ' . $item['order'];
            }
            $result[] = [
                'title' => $label,
                'url'   => $url,
                'type'  => $item['type'],
            ];
        }

        return $result;
    }

    /**
     * Generate COinS span — mirrors Coins::generateCoins().
     *
     * @param array $metadata return value of MetsDocument::getTitleData()
     * @return string
     */
    public function getCoinsHtml(array $metadata): string
    {
        $coins  = 'url_ver=Z39.88-2004';
        $coins .= '&ctx_ver=Z39.88-2004';
        $coins .= '&rft_val_fmt=' . urlencode('info:ofi/fmt:kev:mtx:journal');
        $coins .= '&rft.genre=unknown';

        foreach ($metadata as $indexName => $values) {
            if (preg_match('/^author\d+/', $indexName) && is_array($values)) {
                foreach ($values as $value) {
                    if ($value) {
                        $coins .= '&rft.au=' . urlencode((string)$value);
                    }
                }
                continue;
            }
            if (preg_match('/^publisher\d+/', $indexName) && is_array($values)) {
                foreach ($values as $value) {
                    if ($value) {
                        $coins .= '&rft.pub=' . urlencode((string)$value);
                    }
                }
                continue;
            }
            $v = is_array($values) ? ($values[0] ?? null) : $values;
            if (empty($v)) {
                continue;
            }
            switch ($indexName) {
                case 'record_id':              $coins .= '&rfr_id=info:sid/qucosa.de:' . urlencode((string)$v); break;
                case 'urn': case 'original_urn': case 'series_urn': case 'multivolume_urn':
                case 'doi': case 'original_doi':
                    $coins .= '&rft_id=' . urlencode((string)$v); break;
                case 'isbn': case 'original_isbn': $coins .= '&rft.isbn=' . urlencode((string)$v); break;
                case 'issn': case 'original_issn': $coins .= '&rft.issn=' . urlencode((string)$v); break;
                case 'title':                  $coins .= '&rft.atitle=' . urlencode((string)$v); break;
                case 'original_subtitle':      $coins .= '&rft.stitle=' . urlencode((string)$v); break;
                case 'original_title':         $coins .= '&rft.jtitle=' . urlencode((string)$v); break;
                case 'original_pages':         $coins .= '&rft.spage=' . urlencode((string)$v); break;
                case 'original_pages2':        $coins .= '&rft.epage=' . urlencode((string)$v); break;
                case 'issue': case 'original_issue':   $coins .= '&rft.issue=' . urlencode((string)$v); break;
                case 'volume': case 'original_volume': $coins .= '&rft.volume=' . urlencode((string)$v); break;
                case 'original_corporation_publisher': $coins .= '&rft.pub=' . urlencode((string)$v); break;
                case 'place': case 'original_place':   $coins .= '&rft.place=' . urlencode((string)$v); break;
                case 'dateissued':
                    $coins .= '&rft.date=' . urlencode($this->safelyFormatDate('Y/m/d', (string)$v)); break;
                case 'language':               $coins .= '&rft.language=' . urlencode((string)$v); break;
            }
        }

        return '<span class="Z3988" title="' . $coins . '"></span>';
    }

    /**
     * Return meta tag name → values array — mirrors MetaTags::printMetaTags().
     * The controller injects these into <head> via PageRenderer::addMetaTag().
     *
     * @param MetsDocument $doc
     * @param array $metadata return value of MetsDocument::getTitleData()
     * @param array $settings
     * @return array [tagName => [value, ...], ...]
     */
    public function getMetaTags(MetsDocument $doc, array $metadata, array $settings): array
    {
        $out     = [];
        $apiPid  = (int)($settings['apiPid'] ?? 0);
        $fileGrp = $settings['fileGrpDownload'] ?? '';
        $lang    = $GLOBALS['TSFE']->lang ?? 'de';

        foreach ($metadata as $indexName => $values) {
            if (preg_match('/^author\d+/', $indexName) && is_array($values)) {
                foreach ($values as $v) {
                    if ($v) {
                        $out['citation_author'][] = $v;
                    }
                }
            }
            $v = is_array($values) ? ($values[0] ?? null) : $values;
            switch ($indexName) {
                case 'title':
                    if ($v) {
                        $out['citation_title'][] = $v;
                        $GLOBALS['TSFE']->page['title'] = $v;
                    }
                    break;
                case 'dateissued':
                    if ($v) {
                        $out['citation_online_date'][] = $this->safelyFormatDate('Y/m/d', (string)$v);
                    }
                    break;
                case 'publication_date':
                    if ($v) {
                        $out['citation_publication_date'][] = $this->safelyFormatDate('Y', (string)$v);
                    }
                    break;
                case 'abstract_ger':
                    if ($v && $lang === 'de') {
                        $out['description'][] = $v;
                    }
                    break;
                case 'abstract_eng':
                    if ($v && $lang === 'en') {
                        $out['description'][] = $v;
                    }
                    break;
            }
        }

        // citation_pdf_url — one per downloadable attachment
        if (!empty($fileGrp)) {
            $cObj        = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $attachments = $this->getRawAttachments($doc, $fileGrp);
            $qid         = $doc->recordId;
            foreach ($attachments as $file) {
                $url = $cObj->typoLink_URL([
                    'useCacheHash'     => 0,
                    'parameter'        => $apiPid,
                    'additionalParams' => '&tx_dpf_getfile[qid]=' . rawurlencode((string)$qid)
                        . '&tx_dpf_getfile[action]=attachment'
                        . '&tx_dpf_getfile[attachment]=' . $file['ID'],
                    'forceAbsoluteUrl' => true,
                ]);
                $out['citation_pdf_url'][] = $url;
            }
        }

        return $out;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function getRawAttachments(MetsDocument $doc, string $fileGrp): array
    {
        $xPath = 'mets:fileSec/mets:fileGrp[@USE="' . $fileGrp . '"]/mets:file';
        $files = $doc->mets->xpath($xPath);
        if (!is_array($files)) {
            return [];
        }
        $attachments = [];
        foreach ($files as $file) {
            $entry = [];
            foreach ($file->attributes('mext', true) as $attr => $val) {
                $entry[$attr] = (string)$val;
            }
            foreach ($file->attributes() as $attr => $val) {
                $entry[$attr] = (string)$val;
            }
            $attachments[(string)$entry['ID']] = $entry;
        }
        if (count($attachments) > 1) {
            ksort($attachments);
        }
        return array_values($attachments);
    }

    private function firstXPathValue(\SimpleXMLElement $node, string $xpath): string
    {
        $result = $node->xpath($xpath);
        return !empty($result) ? (string)$result[0] : '';
    }

    /**
     * relatedItem nodes can carry multiple mods:identifier/@type; prefer the
     * types that produce a clickable link (urn, then local) over document order.
     */
    private function preferredIdentifierType(\SimpleXMLElement $node): string
    {
        $types = $node->xpath('mods:identifier/@type');
        if (empty($types)) {
            return '';
        }
        $values = array_map('strval', $types);
        foreach (['urn', 'local'] as $preferred) {
            if (in_array($preferred, $values, true)) {
                return $preferred;
            }
        }
        return $values[0];
    }

    private function safelyFormatDate(string $format, string $date): string
    {
        return strlen($date) === 4 ? $date : date($format, (int)strtotime($date));
    }

    private function parseTS(string $string): array
    {
        $parser = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class);
        $parser->parse($string);
        return $parser->setup;
    }

    private function translateValue(string $indexName, string $value, array $settings): string
    {
        // Extbase settings maps TS foo.bar.baz → $settings['foo']['bar']['baz']
        $labels = $settings['labels'][$indexName] ?? null;
        if (is_array($labels) && isset($labels[$value]) && is_string($labels[$value])) {
            return $labels[$value];
        }
        if ($indexName === 'language') {
            return $this->getLanguageName($value);
        }
        return $value;
    }

    private function getLanguageName(string $code): string
    {
        $isoCode = strtolower(trim($code));
        if (preg_match('/^[a-z]{3}$/', $isoCode)) {
            $file = ExtensionManagementUtility::extPath('dpf') . 'Resources/Private/Data/iso-639-2b.xml';
        } elseif (preg_match('/^[a-z]{2}$/', $isoCode)) {
            $file = ExtensionManagementUtility::extPath('dpf') . 'Resources/Private/Data/iso-639-1.xml';
        } else {
            return $code;
        }
        $iso639 = $GLOBALS['TSFE']->readLLfile($file);
        if (!empty($iso639['default'][$isoCode])) {
            $name = $GLOBALS['TSFE']->getLLL($isoCode, $iso639);
            if (!empty($name)) {
                return $name;
            }
        }
        return $code;
    }
}
