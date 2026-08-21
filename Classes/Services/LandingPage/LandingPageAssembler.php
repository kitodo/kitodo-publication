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
     * @param string $hostUrl absolute URL of this document's host/series relatedItem
     *   (from getHostUrl(getParentItems())), if any. Lets the "Quellenangabe" wrap
     *   rows (e.g. "Zeitschrift", "Konferenzband") link their prose title instead of
     *   showing a redundant unlinked line next to the separate parent-link block (#2039).
     * @return string HTML string including the outer <div><dl> wrapper
     */
    public function getMetadataHtml(MetsDocument $doc, array $metadata, array $settings, string $hostUrl = ''): string
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
            if (is_array($value)) {
                $value = self::joinValues($value, $separator);
            }
            $cObj->data[$indexName] = $value;
        }
        $cObj->data['host_url'] = $hostUrl;

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
     * Return constituent (downward, issue→articles) related items — mirrors
     * RelatedListTool::getRelatedItems(). Rendered as the "contained items"
     * list.
     *
     * @param MetsDocument $doc
     * @param array $settings
     * @return array [['title' => string, 'url' => string|null, 'type' => string], ...]
     */
    public function getRelatedItems(MetsDocument $doc, array $settings): array
    {
        return $this->extractRelatedItems($doc, $settings, '//mods:relatedItem[@type="constituent"]');
    }

    /**
     * Return "host"/"series" (upward, article→issue, issue→journal,
     * book→series) related items — the document's parent/container. Rendered
     * separately near the top of the landing page, not mixed into the
     * "contained items" list below (#2039).
     *
     * @param MetsDocument $doc
     * @param array $settings
     * @return array [['title' => string, 'url' => string|null, 'type' => string], ...]
     */
    public function getParentItems(MetsDocument $doc, array $settings): array
    {
        return $this->extractRelatedItems($doc, $settings, '//mods:relatedItem[@type="host" or @type="series"]');
    }

    /**
     * @param MetsDocument $doc
     * @param array $settings
     * @param string $xpath selects which relatedItem nodes to extract
     * @return array [['title' => string, 'url' => string|null, 'type' => string], ...]
     */
    private function extractRelatedItems(MetsDocument $doc, array $settings, string $xpath): array
    {
        $items = $doc->mets->xpath($xpath);
        if (!is_array($items) || empty($items)) {
            return [];
        }

        $cObj        = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $landingPage = (int)($settings['landingPage'] ?? ($GLOBALS['TSFE']->page['uid'] ?? 0));
        $raw         = [];

        foreach ($items as $node) {
            $node->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');
            $node->registerXPathNamespace('slub', 'http://slub-dresden.de/');

            $relation = $this->firstXPathValue($node, '@type');
            $type    = $this->preferredIdentifierType($node);
            $title   = $this->firstXPathValue($node, 'mods:titleInfo/mods:title');
            $docId   = $type !== '' ? $this->firstXPathValue($node, 'mods:identifier[@type="' . $type . '"]') : '';
            $order   = $this->firstXPathValue($node, 'mods:extension/slub:info/slub:sortingKey');
            $volume  = $this->firstXPathValue($node, 'mods:part[@type="volume" or @type="issue"]/mods:detail/mods:number');

            // A host/series relatedItem stub often carries only an identifier
            // (see qucosa-80960: <relatedItem type="host"> has no titleInfo
            // at all), so the link fell back to the raw URN as its label
            // (#2039). Resolve the real title from the public search index.
            if ($title === '' && $docId !== '') {
                $title = $this->resolveTitleFromPublicIndex($type, $docId);
            }

            // Neither title nor identifier: nothing to display or link
            // (seen live on qucosa-14455 as a visible empty list row).
            if ($title === '' && $docId === '') {
                continue;
            }

            $raw[] = [
                'relation' => $relation,
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
            $url = $this->buildRelatedItemUrl($cObj, $item, $landingPage);

            $label = $item['title'] ?: $item['docId'];
            $result[] = [
                'title'         => $label,
                'url'           => $url,
                'type'          => $item['type'],
                'relation'      => $item['relation'],
                'relationLabel' => $this->relationLabel($item['relation']),
            ];
        }

        return $result;
    }

    /**
     * Builds the clickable URL for one deduped related-item entry, or null
     * when its identifier type has no known link scheme.
     *
     * @param array $item one entry from $raw in extractRelatedItems() (keys: type, docId)
     */
    private function buildRelatedItemUrl(ContentObjectRenderer $cObj, array $item, int $landingPage): ?string
    {
        if ($item['type'] === 'local') {
            return $cObj->typoLink_URL([
                'useCacheHash'     => 1,
                'parameter'        => $landingPage,
                'additionalParams' => '&tx_dpf_landingpage[qid]=' . rawurlencode(strtolower($item['docId'])),
                'forceAbsoluteUrl' => true,
            ]);
        }
        if ($item['type'] === 'urn') {
            return $cObj->typoLink_URL([
                'useCacheHash'     => 0,
                'parameter'        => 'https://nbn-resolving.de/' . $item['docId'],
                'forceAbsoluteUrl' => true,
            ]);
        }
        return null;
    }

    /**
     * Absolute URL of the document's host relatedItem (the journal/issue a
     * Zeitschriftenartikel or Konferenzbeitrag belongs to), if any and if
     * linkable. Used to embed the link into the "Quellenangabe" prose row
     * instead of only showing it in the separate parent-link block (#2039).
     *
     * @param array $parentItems return value of getParentItems()
     */
    public function getHostUrl(array $parentItems): string
    {
        foreach ($parentItems as $item) {
            if (($item['relation'] ?? '') === 'host' && !empty($item['url'])) {
                return (string) $item['url'];
            }
        }
        return '';
    }

    /**
     * Doctypes whose tx_dpf_metadata "Quellenangabe" wrap row (e.g.
     * "Zeitschrift", "Konferenzband") embeds the host link via getHostUrl()
     * once EmbedHostLinkInQuellenangabeUpdate has run (#2039). For these,
     * the host entry in parentItems is dropped so it isn't shown twice.
     */
    private const DOCTYPES_WITH_EMBEDDED_HOST_LINK = ['article', 'in_proceeding'];

    /**
     * @param array $parentItems return value of getParentItems()
     * @param string $type the document's "type" field (mods:genre)
     * @return array $parentItems with the host entry removed for doctypes
     *   whose citation prose already links it; series entries are untouched.
     */
    public function filterEmbeddedHostItems(array $parentItems, string $type): array
    {
        if (!in_array($type, self::DOCTYPES_WITH_EMBEDDED_HOST_LINK, true)) {
            return $parentItems;
        }
        return array_values(array_filter($parentItems, static function (array $item): bool {
            return ($item['relation'] ?? '') !== 'host';
        }));
    }

    /**
     * The parentItems the template should actually render: with the host
     * entry dropped only when the Quellenangabe prose row will actually
     * render a linked title for it — i.e. both of the wrap row's own
     * fieldRequired guards are met (non-empty original_title, non-empty
     * host_url). Otherwise the host entry stays, so a record never ends up
     * with neither a linked prose title nor a parentItems fallback (#2039).
     *
     * @param array $parentItems return value of getParentItems()
     * @param string $hostUrl return value of getHostUrl($parentItems)
     * @param string $originalTitle $metadata['original_title'][0] ?? ''
     * @param string $type the document's "type" field (mods:genre)
     */
    public function getVisibleParentItems(
        array $parentItems,
        string $hostUrl,
        string $originalTitle,
        string $type
    ): array {
        if ($hostUrl === '' || $originalTitle === '') {
            return $parentItems;
        }
        return $this->filterEmbeddedHostItems($parentItems, $type);
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
     * Looks up a related item's title in the public search index when the
     * relatedItem stub itself carries none (#2039). Never throws — any
     * lookup failure (ES down, doc not indexed) leaves the caller to fall
     * back to the raw identifier as before.
     */
    private function resolveTitleFromPublicIndex(string $type, string $docId): string
    {
        try {
            $index = GeneralUtility::makeInstance(\EWW\Dpf\Services\ElasticSearch\PublicElasticSearch::class);

            if ($type === 'local') {
                $doc = $index->getDocument(strtolower($docId));
                $titles = $doc['_source']['title'] ?? [];
                return !empty($titles) ? (string)$titles[0] : '';
            }

            if ($type === 'urn') {
                $results = $index->search([
                    'body' => ['query' => ['term' => ['identifier.keyword' => $docId]]],
                ]);
                return $this->pickOwnTitleFromSearchHits($results['hits']['hits'] ?? [], $docId);
            }
        } catch (\Throwable $e) {
            // ES unreachable or doc not indexed — caller falls back to docId as label.
        }

        return '';
    }

    /**
     * A relatedItem's URN can match several public-index docs at once: the
     * target itself, plus every sibling that merely links to it via a host
     * relation (getSearchIdentifiers() flattens inherited identifiers into
     * the same field — see qucosa-80960's journal URN, shared by 8 issues
     * in the index). The target is the one hit whose own identifier list
     * contains this URN exactly once — siblings carry it twice (their own
     * URN plus the inherited one).
     *
     * @param array $hits ES hits array (each with '_source' => ['title' => [...], 'identifier' => [...]])
     * @param string $urn the queried URN
     * @return string
     */
    public function pickOwnTitleFromSearchHits(array $hits, string $urn): string
    {
        foreach ($hits as $hit) {
            $identifiers = $hit['_source']['identifier'] ?? [];
            $urnCount = count(array_filter($identifiers, static function ($id) {
                return strpos((string)$id, 'urn:') === 0;
            }));
            if ($urnCount === 1 && in_array($urn, $identifiers, true)) {
                $titles = $hit['_source']['title'] ?? [];
                return !empty($titles) ? (string)$titles[0] : '';
            }
        }
        return '';
    }

    /**
     * German label for a relatedItem's own @type (host/series/constituent),
     * shown above the parent link so it isn't a bare unlabeled URL (#2039).
     */
    private function relationLabel(string $relation): string
    {
        $labels = [
            'host'   => 'Erschienen in',
            'series' => 'Schriftenreihe',
        ];
        return $labels[$relation] ?? '';
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

    /**
     * Join multi-value field values, skipping empty and whitespace-only
     * entries — empty MODS elements (e.g. <mods:subTitle/>) extract as empty
     * strings and would otherwise produce stray separators in the output.
     *
     * @param array $values
     * @param string $separator
     * @return string
     */
    private static function joinValues(array $values, string $separator): string
    {
        $nonEmpty = [];
        foreach ($values as $value) {
            if (trim((string)$value) !== '' && !in_array($value, $nonEmpty, true)) {
                $nonEmpty[] = $value;
            }
        }

        return implode($separator, $nonEmpty);
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
