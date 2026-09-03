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
     * Doctypes whose tx_dpf_metadata "Quellenangabe" wrap row (e.g.
     * "Zeitschrift", "Konferenzband") embeds the host link via {field:host_url}
     * once EmbedHostLinkInQuellenangabeUpdate has run (#2039). Every other
     * doctype's host relatedItem is not consumed by any wrap row, so it must
     * still be spliced into the <dl> by resolveEmbeddableParentItems() below
     * — keep this list in sync with EmbedHostLinkInQuellenangabeUpdate's
     * AFFECTED_INDEX_NAMES (original0000000000=article, original_in_proceeding0000000=in_proceeding)
     * and FixSammelbandHostLinkAndFieldOrderUpdate (original_in_book=contained_work, #2040 item 5).
     */
    private const DOCTYPES_WITH_EMBEDDED_HOST_LINK = ['article', 'in_proceeding', 'contained_work'];

    /**
     * index_name of the dead "Erschienen in" placeholder rows (empty xpath,
     * kept only for their `sorting` value) — one per legacy doctype that
     * used to show a host link there. Does NOT include `original_in_media`
     * (uid 537, also labelled "Erschienen in"): that's a live Quellenangabe
     * citation row from AddTypeSpecificSourceCitationRowsUpdate, not a
     * placeholder, and must never be treated as a splice point.
     */
    private const HOST_PLACEHOLDER_INDEX_NAMES = [
        'multivolume_proceeding',
        'multivolume0000',
        'multivolume_issue00',
        'multivolume_lecture0',
        'multivolume_monograph0',
        'multivolume_doctoral_thesis',
    ];

    /** index_name of the dead "Schriftenreihe" placeholder row (empty xpath). */
    private const SERIES_PLACEHOLDER_INDEX_NAME = 'series0';

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
     * @param array $parentItems return value of getParentItems(). Host/series entries not
     *   already embedded via $hostUrl are spliced into the <dl> at the position of their
     *   now-dead legacy placeholder row (e.g. "multivolume_proceeding", "series0" — empty
     *   xpath, kept only for their `sorting` value), matching where Kitodo.Presentation's
     *   metadata plugin used to show them (#2039: order parity with the legacy page, where
     *   "Erschienen in" sat right after the title fields, not at the very bottom).
     * @return array{html: string, embeddedRelations: array{host: bool, series: bool}}
     */
    public function getMetadataHtml(
        MetsDocument $doc,
        array $metadata,
        array $settings,
        string $hostUrl = '',
        array $parentItems = []
    ): array {
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

        [$hostItem, $seriesItem, $embedded] = $this->resolveEmbeddableParentItems($parentItems, $hostUrl, $metadata);

        $inner = '';
        foreach ($metaList as $indexName => $metaConf) {
            $fieldwrap   = $this->parseTS($metaConf['wrap']);
            $parsedValue = $this->parseFieldValue($cObj, $local, $indexName, $fieldwrap, $settings);

            if (!empty($parsedValue)) {
                $field  = $cObj->stdWrap(htmlspecialchars($metaConf['label']), $fieldwrap['key.'] ?? []);
                $field .= $parsedValue;
                $inner .= $cObj->stdWrap($field, $fieldwrap['all.'] ?? []);
            } elseif (
                $hostItem !== null
                && !$embedded['host']
                && in_array($indexName, self::HOST_PLACEHOLDER_INDEX_NAMES, true)
            ) {
                $inner .= $this->renderEmbeddedParentItemRow($hostItem);
                $embedded['host'] = true;
            } elseif (
                $seriesItem !== null
                && !$embedded['series']
                && $indexName === self::SERIES_PLACEHOLDER_INDEX_NAME
            ) {
                $inner .= $this->renderEmbeddedParentItemRow($seriesItem);
                $embedded['series'] = true;
            }
        }

        [$inner, $embedded] = $this->appendUnplacedParentItems($inner, $hostItem, $seriesItem, $embedded);

        $html = '<div class="tx-dpf-metadata tx-dlf-metadata"><div><dl>' . $inner . '</dl></div></div>';
        return ['html' => $html, 'embeddedRelations' => $embedded];
    }

    /**
     * Renders one field's value(s) through its wrap TypoScript — mirrors the
     * PI plugin's per-field loop (repeatable fields shift/join via
     * array_shift on $local until exhausted).
     *
     * @param array $local metadata values, consumed via array_shift as repeatable fields are read
     * @param array $fieldwrap parsed TypoScript for this field (key./value./all.)
     * @param array $settings Extbase settings, needed by translateValue()
     */
    private function parseFieldValue(
        ContentObjectRenderer $cObj,
        array &$local,
        string $indexName,
        array $fieldwrap,
        array $settings
    ): string {
        $parsedValue = '';
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

        return $parsedValue;
    }

    /**
     * Picks the one host and one series entry (if any) getMetadataHtml()
     * should try to splice into the <dl>, and pre-marks 'host' as already
     * embedded when the Quellenangabe prose row will link it itself (item 2,
     * #2039) — so neither the placeholder-splice nor the end-of-list
     * fallback fire for it a second time.
     *
     * @param array $parentItems return value of getParentItems()
     * @param string $hostUrl return value of getHostUrl($parentItems)
     * @param array $metadata return value of MetsDocument::getTitleData()
     * @return array{0: array|null, 1: array|null, 2: array{host: bool, series: bool}}
     */
    private function resolveEmbeddableParentItems(array $parentItems, string $hostUrl, array $metadata): array
    {
        $type = (string) ($metadata['type'][0] ?? '');
        $hostAlreadyInProse = $hostUrl !== ''
            && !empty($metadata['original_title'][0] ?? null)
            && in_array($type, self::DOCTYPES_WITH_EMBEDDED_HOST_LINK, true);
        $hostItem   = $hostAlreadyInProse ? null : $this->firstByRelation($parentItems, 'host');
        $seriesItem = $this->firstByRelation($parentItems, 'series');
        return [$hostItem, $seriesItem, ['host' => $hostAlreadyInProse, 'series' => false]];
    }

    /**
     * Appends host/series rows that never matched their dead-placeholder
     * label while walking $metaList (e.g. a doctype absent from the legacy
     * tx_dlf_metadata table) — same fallback position the code used before
     * the #2039 ordering fix.
     *
     * @return array{0: string, 1: array{host: bool, series: bool}}
     */
    private function appendUnplacedParentItems(string $inner, ?array $hostItem, ?array $seriesItem, array $embedded): array
    {
        if ($hostItem !== null && !$embedded['host']) {
            $inner .= $this->renderEmbeddedParentItemRow($hostItem);
            $embedded['host'] = true;
        }
        if ($seriesItem !== null && !$embedded['series']) {
            $inner .= $this->renderEmbeddedParentItemRow($seriesItem);
            $embedded['series'] = true;
        }
        return [$inner, $embedded];
    }

    /**
     * @param array $parentItems return value of getParentItems()
     */
    private function firstByRelation(array $parentItems, string $relation): ?array
    {
        foreach ($parentItems as $item) {
            if (($item['relation'] ?? '') === $relation) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Renders one parentItems entry as a <dt>/<dd> pair matching the markup the
     * standalone parent-link partial (Show.html) used to produce, so embedding it
     * into the metadata <dl> is visually identical to the block it replaces.
     *
     * @param array $item one entry from getParentItems() (keys: title, url, relationLabel)
     */
    private function renderEmbeddedParentItemRow(array $item): string
    {
        $label = htmlspecialchars($item['relationLabel']);
        $title = htmlspecialchars($item['title']);
        $value = $title;
        if (!empty($item['url'])) {
            $value = '<a href="' . htmlspecialchars($item['url']) . '">' . $title . '</a>';
        }
        return '<dt>' . $label . '</dt><dd>' . $value . '</dd>';
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
     * Return "preceding"/"succeeding" (Vorgänger/Nachfolger) related items -
     * this document's place in a sequence, not a parent/container relation.
     * Rendered in the same slot as parentItems (#2039/#2046): the disseminator
     * now derives "succeeding" by reverse lookup since Qucosa never stores it
     * directly, only "preceding".
     *
     * @param MetsDocument $doc
     * @param array $settings
     * @return array [['title' => string, 'url' => string|null, 'type' => string], ...]
     */
    public function getSequenceItems(MetsDocument $doc, array $settings): array
    {
        return $this->extractRelatedItems($doc, $settings, '//mods:relatedItem[@type="preceding" or @type="succeeding"]');
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
            $type    = \EWW\Dpf\Common\ModsIdentifier::preferredType($node);
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
     * The parentItems the standalone template block should still render —
     * i.e. everything getMetadataHtml() did *not* already embed into the
     * <dl> itself, either via the Quellenangabe prose link or the ordering
     * splice (#2039). Superseded the doctype-guessing
     * filterEmbeddedHostItems()/getVisibleParentItems() pair: getMetadataHtml()
     * now reports exactly what it embedded, so no heuristic is needed here.
     *
     * @param array $parentItems return value of getParentItems()
     * @param array $embeddedRelations the 'embeddedRelations' entry from getMetadataHtml()'s return value
     */
    public function filterEmbeddedRelations(array $parentItems, array $embeddedRelations): array
    {
        return array_values(array_filter($parentItems, static function (array $item) use ($embeddedRelations): bool {
            return empty($embeddedRelations[$item['relation'] ?? '']);
        }));
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
                // A shared series/collection URN can have more hits than ES's default
                // page size of 10 (e.g. 14 volumes of one Schriftenreihe, #2039) —
                // without an explicit size the container's own record can be pushed
                // past the cutoff and never reach pickOwnTitleFromSearchHits() at all.
                $results = $index->search([
                    'body' => [
                        'query' => ['term' => ['identifier.keyword' => $docId]],
                        'size'  => 50,
                    ],
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
     * in the index). The target is normally the one hit whose own identifier
     * list contains this URN exactly once — siblings carry it twice (their
     * own URN plus the inherited one).
     *
     * That count-based rule assumes the target's only URN is the shared one.
     * A multivolume-work container can carry its own distinct URN *and* the
     * shared one (qucosa-35005/urn:...334170, #2039) — two URNs, so it never
     * matches the count rule even though it's genuinely the target. For that
     * case, fall back to doctype: a hit whose own doctype marks it as a
     * container (periodical/series/multivolume_work) is the target regardless
     * of how many URNs it carries.
     *
     * @param array $hits ES hits array (each with '_source' => ['title' => [...], 'identifier' => [...], 'doctype' => string])
     * @param string $urn the queried URN
     * @return string
     */
    public function pickOwnTitleFromSearchHits(array $hits, string $urn): string
    {
        static $containerDoctypes = ['periodical', 'series', 'multivolume_work'];

        // No ambiguity to resolve: the heuristics below only exist to pick
        // the right one out of several docs sharing an inherited URN. A
        // single hit can legitimately carry >1 URN itself (e.g. an "issue"
        // that is itself part of a host with a shared URN, qucosa-11116)
        // without being a "container" doctype - don't let those heuristics
        // reject the only candidate there is (#2041).
        if (count($hits) === 1) {
            return $this->hitTitle($hits[0]);
        }

        foreach ($hits as $hit) {
            $identifiers = $hit['_source']['identifier'] ?? [];
            $urnCount = count(array_filter($identifiers, static function ($id) {
                return strpos((string)$id, 'urn:') === 0;
            }));
            if ($urnCount === 1 && in_array($urn, $identifiers, true)) {
                return $this->hitTitle($hit);
            }
        }

        foreach ($hits as $hit) {
            $identifiers = $hit['_source']['identifier'] ?? [];
            $doctype = (string)($hit['_source']['doctype'] ?? '');
            if (in_array($doctype, $containerDoctypes, true) && in_array($urn, $identifiers, true)) {
                return $this->hitTitle($hit);
            }
        }

        return '';
    }

    /**
     * @param array $hit one ES hit (with '_source' => ['title' => [...]])
     */
    private function hitTitle(array $hit): string
    {
        $titles = $hit['_source']['title'] ?? [];
        return !empty($titles) ? (string)$titles[0] : '';
    }

    /**
     * German label for a relatedItem's own @type (host/series/constituent),
     * shown above the parent link so it isn't a bare unlabeled URL (#2039).
     */
    private function relationLabel(string $relation): string
    {
        $labels = [
            'host'       => 'Erschienen in',
            'series'     => 'Schriftenreihe',
            'preceding'  => 'Vorgänger',
            'succeeding' => 'Nachfolger',
        ];
        return $labels[$relation] ?? '';
    }

    /**
     * relatedItem nodes can carry multiple mods:identifier/@type; prefer the
     * types that produce a clickable link (urn, then local) over document order.
     */
    private function safelyFormatDate(string $format, string $date): string
    {
        return strlen($date) === 4 ? $date : date($format, (int)strtotime($date));
    }

    /**
     * Looks up embargo status for the current document in the public search
     * index (#1985/#2039 display piece only - the actual download block is
     * enforced separately at publish time via Document::publicXml(), not
     * here). No METS/MODS element carries embargo info, so this can't be
     * read from $doc like other fields. Never throws; a lookup failure or a
     * past/absent embargo date both render nothing.
     *
     * @return array{isEmbargoed: bool, embargoDate: string} embargoDate is
     *   '' when not currently embargoed.
     */
    public function getEmbargoInfo(string $qid): array
    {
        $notEmbargoed = ['isEmbargoed' => false, 'embargoDate' => ''];

        try {
            $index = GeneralUtility::makeInstance(\EWW\Dpf\Services\ElasticSearch\PublicElasticSearch::class);
            $document = $index->getDocument(strtolower($qid));
            $embargoDate = (string)($document['_source']['embargoDate'] ?? '');
        } catch (\Throwable $e) {
            return $notEmbargoed;
        }

        if (!$this->isFutureDate($embargoDate)) {
            return $notEmbargoed;
        }

        return [
            'isEmbargoed' => true,
            'embargoDate' => $this->safelyFormatDate('d.m.Y', $embargoDate),
        ];
    }

    /**
     * @param string $date empty string, or any format strtotime() accepts
     */
    private function isFutureDate(string $date): bool
    {
        if ($date === '') {
            return false;
        }
        $timestamp = strtotime($date);
        return $timestamp !== false && $timestamp > time();
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
