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
 *
 * #2047's embargo-date fix tips this class a few lines over PHPMD's 1000
 * LOC threshold; it was already at the ceiling before this change.
 * Splitting it (e.g. one assembler per legacy-PI-plugin area, matching the
 * docblock above) is a real fix but out of scope for a bug fix - own
 * follow-up.
 *
 * @SuppressWarnings("PHPMD.ExcessiveClassLength")
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
    private const DOCTYPES_WITH_EMBEDDED_HOST_LINK = ['article', 'in_proceeding', 'contained_work', 'preprint'];

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
     * #2047 (UBL-26-5088): sub-field suffix => display label, or null for the
     * one field (note) that's shown unprefixed. Same shape for both the host
     * ("multivolume_*") and series ("series_*") relatedItem, so one field list
     * drives both — see relationDetailLines(). Order matches the ticket's
     * "Soll" example and AddRelationDetailFieldsUpdate's own field order.
     */
    private const RELATION_DETAIL_FIELDS = [
        'note'   => null,
        'url'    => 'URL',
        'doi'    => 'DOI',
        'handle' => 'Handle',
        'isbn'   => 'ISBN',
        'issn'   => 'ISSN',
        'zdb'    => 'ZDB-ID',
    ];

    /**
     * Second-look identifier fields for a person's mods:name node (#2047,
     * UBL-26-5007): label => xpath relative to that name node. Order is
     * display order in the title="" tooltip.
     */
    private const PERSON_TOOLTIP_FIELDS = [
        'Institutionszugehörigkeit' => 'mods:affiliation',
        'ORCID' => 'mods:nameIdentifier[@type="ORCID"]',
        'ResearcherID' => 'mods:nameIdentifier[@type="ResearcherID"]',
        'GND-ID' => 'mods:nameIdentifier[@type="gnd"]',
        'Scopus Author ID' => 'mods:nameIdentifier[@type="ScopusAuthorID"]',
    ];

    /** Same as PERSON_TOOLTIP_FIELDS, for an institution's mods:name node. */
    private const INSTITUTION_TOOLTIP_FIELDS = [
        'GND-ID' => 'mods:nameIdentifier[@type="gnd"]',
        'ROR-ID' => 'mods:nameIdentifier[@type="ROR"]',
    ];

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

        // #2040 item 5: some doctypes' host relatedItem carries no titleInfo in this
        // document's own METS - the title lives only on the target record and is
        // resolved via ES by getParentItems()/extractRelatedItems(). Fall back to that
        // already-resolved title so the Quellenangabe prose row's {field:original_title}
        // link text isn't left empty (and hostAlreadyInProse below can trigger correctly).
        if (empty($metadata['original_title'][0] ?? null)) {
            $resolvedHostItem = $this->firstByRelation($parentItems, 'host');
            if ($resolvedHostItem !== null && !empty($resolvedHostItem['title'])) {
                $metadata['original_title'] = [$resolvedHostItem['title']];
            }
        }

        $institutionTooltips = $this->injectNameIdentifierTooltips($doc, $metadata);

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
        $hostDetailLines   = $this->relationDetailLines($metadata, 'multivolume_');
        $seriesDetailLines = $this->relationDetailLines($metadata, 'series_');

        $inner = '';
        foreach ($metaList as $indexName => $metaConf) {
            $fieldwrap   = $this->parseTS($metaConf['wrap']);
            $tooltips    = $institutionTooltips[$indexName] ?? [];
            $parsedValue = $this->parseFieldValue($cObj, $local, $indexName, $fieldwrap, $settings, $tooltips);

            if (!empty($parsedValue)) {
                $field  = $cObj->stdWrap(htmlspecialchars($metaConf['label']), $fieldwrap['key.'] ?? []);
                $field .= $parsedValue;
                $inner .= $cObj->stdWrap($field, $fieldwrap['all.'] ?? []);
            } elseif (
                $hostItem !== null
                && !$embedded['host']
                && in_array($indexName, self::HOST_PLACEHOLDER_INDEX_NAMES, true)
            ) {
                $inner .= $this->renderEmbeddedParentItemRow($hostItem, $hostDetailLines);
                $embedded['host'] = true;
            } elseif (
                $seriesItem !== null
                && !$embedded['series']
                && $indexName === self::SERIES_PLACEHOLDER_INDEX_NAME
            ) {
                $inner .= $this->renderEmbeddedParentItemRow($seriesItem, $seriesDetailLines);
                $embedded['series'] = true;
            }
        }

        [$inner, $embedded] = $this->appendUnplacedParentItems(
            $inner,
            $hostItem,
            $seriesItem,
            $embedded,
            $hostDetailLines,
            $seriesDetailLines
        );

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
     * @param string[] $tooltips per-position title="" text (#2047), consumed alongside $local
     */
    private function parseFieldValue(
        ContentObjectRenderer $cObj,
        array &$local,
        string $indexName,
        array $fieldwrap,
        array $settings,
        array $tooltips = []
    ): string {
        $parsedValue = '';
        do {
            // Mirrors PI plugin: @array_shift on potentially non-array key (e.g. 'authors'
            // has no XPath rule; value comes from cObj->data via value.override.insertData)
            $value   = is_array($local[$indexName] ?? null) ? array_shift($local[$indexName]) : null;
            $tooltip = array_shift($tooltips);
            if ($indexName === 'title') {
                $value = !empty($value) ? htmlspecialchars((string)$value) : '';
            } elseif (in_array($indexName, ['owner', 'type', 'collection', 'language', 'peer_review'], true) && !empty($value)) {
                $value = htmlspecialchars($this->translateValue($indexName, (string)$value, $settings));
            } elseif (!empty($value)) {
                $value = htmlspecialchars((string)$value);
            }
            $value = $this->wrapWithTooltip((string)$value, $tooltip);
            $value = $cObj->stdWrap($value, $fieldwrap['value.'] ?? []);
            if (!empty($value)) {
                $parsedValue .= $value;
            }
        } while (!empty($local[$indexName]));

        return $parsedValue;
    }

    /**
     * #2047: wraps an already-escaped field value in a title="" tooltip span, or returns it
     * unchanged when there's nothing to show (no value, or no tooltip for this position).
     */
    private function wrapWithTooltip(string $value, ?string $tooltip): string
    {
        if (empty($value) || empty($tooltip)) {
            return $value;
        }

        return '<span title="' . $tooltip . '">' . $value . '</span>';
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
    private function appendUnplacedParentItems(
        string $inner,
        ?array $hostItem,
        ?array $seriesItem,
        array $embedded,
        string $hostDetailLines = '',
        string $seriesDetailLines = ''
    ): array {
        if ($hostItem !== null && !$embedded['host']) {
            $inner .= $this->renderEmbeddedParentItemRow($hostItem, $hostDetailLines);
            $embedded['host'] = true;
        }
        if ($seriesItem !== null && !$embedded['series']) {
            $inner .= $this->renderEmbeddedParentItemRow($seriesItem, $seriesDetailLines);
            $embedded['series'] = true;
        }
        return [$inner, $embedded];
    }

    /**
     * #2047 (UBL-26-5088): renders the Bemerkung/URL/DOI/Handle/ISBN/ISSN/
     * ZDB-ID sub-fields of a host or series relatedItem as extra <dd> lines,
     * so renderEmbeddedParentItemRow() can merge them into the same
     * "Erschienen in"/"Schriftenreihe" block instead of each living in its
     * own standalone tx_dpf_metadata row (AddRelationDetailFieldsUpdate).
     * Those standalone rows stay hidden=1 (HideRelationDetailStandaloneRowsUpdate)
     * so they keep feeding $metadata without also rendering their own <dt>.
     *
     * @param array $metadata return value of MetsDocument::getTitleData()
     * @param string $prefix 'multivolume_' (host) or 'series_' (series)
     */
    private function relationDetailLines(array $metadata, string $prefix): string
    {
        $lines = '';
        foreach (self::RELATION_DETAIL_FIELDS as $suffix => $label) {
            foreach ((array)($metadata[$prefix . $suffix] ?? []) as $value) {
                if ($value === '' || $value === null) {
                    continue;
                }
                $text   = htmlspecialchars((string)$value);
                $lines .= '<dd>' . ($label !== null ? $label . ':&nbsp;' : '') . $text . '</dd>';
            }
        }
        return $lines;
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
     * #2047 (UBL-26-5088): also appends the target's volume/Bandzählung
     * inline after the title (ticket's "Soll": "Titel, Band") and, when
     * $detailLines is given, the relatedItem's Bemerkung/URL/DOI/Handle/
     * ISBN/ISSN/ZDB-ID as further <dd> lines in the same block — see
     * relationDetailLines().
     *
     * @param array $item one entry from getParentItems() (keys: title, url, relationLabel, volume)
     * @param string $detailLines pre-rendered <dd> lines from relationDetailLines(), or ''
     */
    private function renderEmbeddedParentItemRow(array $item, string $detailLines = ''): string
    {
        $label = htmlspecialchars($item['relationLabel']);
        $title = htmlspecialchars($item['title']);
        $value = $title;
        if (!empty($item['url'])) {
            $value = '<a href="' . htmlspecialchars($item['url']) . '">' . $title . '</a>';
        }
        if (!empty($item['volume'])) {
            $value .= ', ' . htmlspecialchars((string)$item['volume']);
        }
        return '<dt>' . $label . '</dt><dd>' . $value . '</dd>' . $detailLines;
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
            $targetObjectIdentifier = '';
            if ($title === '' && $docId !== '') {
                $resolved = $this->resolveTitleFromPublicIndex($type, $docId);
                $title = $resolved['title'];
                $targetObjectIdentifier = $resolved['objectIdentifier'];
            }

            if (in_array($relation, ['preceding', 'succeeding'], true)) {
                $targetId = $type === 'local' ? $docId : $targetObjectIdentifier;
                $title = $this->appendIssueDesignation($title, $targetId, $settings);
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
                'volume'        => $item['volume'],
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
            // No 'useCacheHash' key here: it's not a real typoLink_URL() config
            // option in this TYPO3 version (only ever internally overwritten from
            // the current request's own cHash, never settable by a caller - see
            // ContentObjectRenderer::typoLink()). The actual fix for the
            // Vorgänger/Nachfolger link's cHash (#2046) is
            // tx_dpf_landingpage[qid]'s cacheHash.excludedParameters entry below.
            return $cObj->typoLink_URL([
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
     * #2047 (UBL-26-5007): affiliation/ORCID/ResearcherID/GND-ID/Scopus Author ID (person) and
     * GND-ID/ROR-ID (institution) should be visible on second look, not inline - a native title=""
     * tooltip on the name. mods:nameIdentifier/mods:affiliation nest inside each mods:name node, so
     * they're correlated per-position, not a flat field. Personal roles (author/publisher) render
     * via a numbered value.override chain in their wrap - writes positions 1-3 into $metadata as
     * authorIds1../publisherIds1.. pseudo-fields, matching this codebase's existing slot-depth
     * convention. Institution roles (corporation_author/_editor) render via parseFieldValue()'s
     * plain array_shift loop instead, so their tooltips are returned for the caller to shift in
     * directly there (all positions, not capped at 3).
     *
     * @param array $metadata mutated in place with the authorIds1../publisherIds1.. pseudo-fields
     * @return array{corporation_author: string[], corporation_editor: string[]}
     */
    private function injectNameIdentifierTooltips(MetsDocument $doc, array &$metadata): array
    {
        // //mods:mods/mods:name (direct children of the mods root) - not .//mods:name, which would
        // also match names nested under mods:relatedItem (a Quellenangabe's own author/editor), a
        // different role entirely with its own original_author/original_publisher rows.
        foreach ([
            'authorIds' => ['//mods:mods/mods:name[@type="personal"][mods:role/mods:roleTerm="aut"]', self::PERSON_TOOLTIP_FIELDS, 3],
            'publisherIds' => ['//mods:mods/mods:name[@type="personal"][mods:role/mods:roleTerm="edt"]', self::PERSON_TOOLTIP_FIELDS, 3],
        ] as $fieldPrefix => [$nameXpath, $tooltipFields, $limit]) {
            foreach ($this->buildNameIdentifierTooltips($doc, $nameXpath, $tooltipFields, $limit) as $i => $tooltip) {
                $metadata[$fieldPrefix . ($i + 1)] = [$tooltip];
            }
        }

        return [
            'corporation_author' => $this->buildNameIdentifierTooltips(
                $doc,
                '//mods:mods/mods:name[@type="corporate"][mods:role/mods:roleTerm="aut"]',
                self::INSTITUTION_TOOLTIP_FIELDS
            ),
            'corporation_editor' => $this->buildNameIdentifierTooltips(
                $doc,
                '//mods:mods/mods:name[@type="corporate"][mods:role/mods:roleTerm="edt"]',
                self::INSTITUTION_TOOLTIP_FIELDS
            ),
        ];
    }

    /**
     * Builds one title="" tooltip string per mods:name node matched by
     * $nameXpath, from that node's own nested fields (#2047) - affiliation
     * and nameIdentifiers sit inside each mods:name, correlated per person/
     * institution, not a flat list. Empty for a position with no such data.
     *
     * @param MetsDocument $doc
     * @param string $nameXpath selects the ordered mods:name nodes for one role
     * @param array $fields label => xpath relative to each name node (PERSON_TOOLTIP_FIELDS / INSTITUTION_TOOLTIP_FIELDS)
     * @param int $limit max positions to build (institution roles: unbounded)
     * @return string[] 0-indexed, in document order; htmlspecialchars'd for use in a title attribute
     */
    private function buildNameIdentifierTooltips(MetsDocument $doc, string $nameXpath, array $fields, int $limit = PHP_INT_MAX): array
    {
        $nodes = $doc->mets->xpath($nameXpath);
        if (!is_array($nodes)) {
            return [];
        }

        $tooltips = [];
        foreach (array_slice($nodes, 0, $limit) as $node) {
            $node->registerXPathNamespace('mods', 'http://www.loc.gov/mods/v3');
            $parts = [];
            foreach ($fields as $label => $relativeXpath) {
                $value = $this->firstXPathValue($node, $relativeXpath);
                if ($value !== '') {
                    $parts[] = $label . ': ' . $value;
                }
            }
            $tooltips[] = !empty($parts) ? htmlspecialchars(implode('; ', $parts), ENT_QUOTES) : '';
        }

        return $tooltips;
    }

    /**
     * Looks up a related item's title in the public search index when the
     * relatedItem stub itself carries none (#2039). Never throws — any
     * lookup failure (ES down, doc not indexed) leaves the caller to fall
     * back to the raw identifier as before.
     *
     * @return array{title: string, objectIdentifier: string} objectIdentifier
     *   is '' when unresolved (used by resolveIssueDesignation() for #2041's
     *   Vorgänger/Nachfolger issue-designation lookup, which needs the
     *   target's real objectIdentifier, not its shared/inherited URN).
     */
    private function resolveTitleFromPublicIndex(string $type, string $docId): array
    {
        try {
            $index = GeneralUtility::makeInstance(\EWW\Dpf\Services\ElasticSearch\PublicElasticSearch::class);

            if ($type === 'local') {
                $doc = $index->getDocument(strtolower($docId));
                $titles = $doc['_source']['title'] ?? [];
                return [
                    'title'            => !empty($titles) ? (string)$titles[0] : '',
                    'objectIdentifier' => (string)($doc['_source']['objectIdentifier'] ?? ''),
                ];
            }

            if ($type === 'urn') {
                // A shared series/collection URN can have more hits than ES's default
                // page size of 10 (e.g. 14 volumes of one Schriftenreihe, #2039) —
                // without an explicit size the container's own record can be pushed
                // past the cutoff and never reach pickOwnHit() at all.
                $results = $index->search([
                    'body' => [
                        'query' => ['term' => ['identifier.keyword' => $docId]],
                        'size'  => 50,
                    ],
                ]);
                $hit = $this->pickOwnHit($results['hits']['hits'] ?? [], $docId);
                return [
                    'title'            => $hit !== null ? $this->hitTitle($hit) : '',
                    'objectIdentifier' => $hit !== null ? (string)($hit['_source']['objectIdentifier'] ?? '') : '',
                ];
            }
        } catch (\Throwable $e) {
            // ES unreachable or doc not indexed — caller falls back to docId as label.
        }

        return ['title' => '', 'objectIdentifier' => ''];
    }

    /**
     * #2041: appends the target's issue designation to a Vorgänger/
     * Nachfolger title, e.g. "Archiv für Epigraphik" + "5,1 (2025)" - split
     * out of extractRelatedItems() to keep it under phpmd's line-count limit.
     */
    private function appendIssueDesignation(string $title, string $targetId, array $settings): string
    {
        if ($title === '') {
            return $title;
        }
        $issue = $this->resolveIssueDesignation($targetId, $settings);
        return $issue !== '' ? $title . ' ' . $issue : $title;
    }

    /**
     * #2041: resolves the target document's own issue designation (e.g.
     * "5,1 (2025)"), matching the legacy system. Not in the relatedItem
     * stub, not in the public search index - only in the target's own
     * MODS, so this is a live METS fetch. Never throws: any failure
     * (Fedora down, no apiPid in a non-frontend context) leaves the title
     * without the designation, same as today.
     */
    private function resolveIssueDesignation(string $objectIdentifier, array $settings): string
    {
        if ($objectIdentifier === '' || empty($settings['apiPid'])) {
            return '';
        }

        try {
            $doc = $this->fetchTargetMetsDocument($objectIdentifier, $settings);
            return $doc !== null ? $this->extractIssueDesignation($doc->mets) : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Public (not just for resolveIssueDesignation()'s live-fetch caller):
     * unit-testable without a Fedora connection, same reasoning as
     * pickOwnTitleFromSearchHits() being public to test without an ES one.
     *
     * @param \SimpleXMLElement $mets a document's METS root, namespaces
     *   already registered (as MetsDocument::getInstance() leaves it)
     */
    public function extractIssueDesignation(\SimpleXMLElement $mets): string
    {
        return $this->firstXPathValue($mets, '//mods:mods/mods:part[@type="issue"]/mods:detail/mods:number');
    }

    /**
     * Fetches another document's METS via the same disseminator mechanism
     * LandingPageController uses for the current document - GetFileController
     * needs a real frontend request context (apiPid, TSFE), so this is a
     * no-op outside one (unit tests, CLI).
     */
    private function fetchTargetMetsDocument(string $objectIdentifier, array $settings): ?MetsDocument
    {
        $apiPid = (int)($settings['apiPid'] ?? 0);

        /** @var ContentObjectRenderer $cObj */
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $metsUrl = $cObj->typoLink_URL([
            'parameter'        => $apiPid,
            'additionalParams' => '&tx_dpf_getfile[qid]=' . rawurlencode($objectIdentifier)
                . '&tx_dpf_getfile[action]=mets',
            'forceAbsoluteUrl' => true,
            'useCacheHash'     => 0,
        ]);

        $doc = MetsDocument::getInstance($metsUrl);
        return ($doc !== null && $doc->ready) ? $doc : null;
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
        $hit = $this->pickOwnHit($hits, $urn);
        return $hit !== null ? $this->hitTitle($hit) : '';
    }

    /**
     * Same disambiguation as pickOwnTitleFromSearchHits(), returning the
     * winning hit itself rather than just its title - #2041's issue-
     * designation lookup also needs the hit's objectIdentifier.
     */
    private function pickOwnHit(array $hits, string $urn): ?array
    {
        static $containerDoctypes = ['periodical', 'series', 'multivolume_work'];

        // No ambiguity to resolve: the heuristics below only exist to pick
        // the right one out of several docs sharing an inherited URN. A
        // single hit can legitimately carry >1 URN itself (e.g. an "issue"
        // that is itself part of a host with a shared URN, qucosa-11116)
        // without being a "container" doctype - don't let those heuristics
        // reject the only candidate there is (#2041).
        if (count($hits) === 1) {
            return $hits[0];
        }

        foreach ($hits as $hit) {
            $identifiers = $hit['_source']['identifier'] ?? [];
            $urnCount = count(array_filter($identifiers, static function ($id) {
                return strpos((string)$id, 'urn:') === 0;
            }));
            if ($urnCount === 1 && in_array($urn, $identifiers, true)) {
                return $hit;
            }
        }

        foreach ($hits as $hit) {
            $identifiers = $hit['_source']['identifier'] ?? [];
            $doctype = (string)($hit['_source']['doctype'] ?? '');
            if (in_array($doctype, $containerDoctypes, true) && in_array($urn, $identifiers, true)) {
                return $hit;
            }
        }

        return null;
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
     * Looks up embargo status and Kollektion/Zweitveröffentlichung membership
     * for the current document in one shared public-index lookup (#1985/
     * #2039/#2047 - both are display pieces only, read from the same
     * already-published document, same host as this request). #2047: an
     * incomplete embargo date (e.g. bare year "2031") skips the future/past
     * check - strtotime("2031") misparses it as a time-of-day, not a year -
     * and is treated as still-embargoed with no end date shown, since we
     * don't actually know.
     *
     * "collections" is populated by PublicDocumentMapper straight from the
     * METS built for the current publish request (see
     * InternalFormat::getCollections()) - not from Fedora RELS-EXT, so there
     * is no indexing-order race to worry about (#2047 recon).
     *
     * @return array{isEmbargoed: bool, embargoDate: string, isSecondaryPublication: bool}
     */
    public function getPublicIndexInfo(string $qid): array
    {
        $none = ['isEmbargoed' => false, 'embargoDate' => '', 'isSecondaryPublication' => false];

        try {
            $index = GeneralUtility::makeInstance(\EWW\Dpf\Services\ElasticSearch\PublicElasticSearch::class);
            $document = $index->getDocument(strtolower($qid));
            $embargoDate = (string)($document['_source']['embargoDate'] ?? '');
            $collections = (array)($document['_source']['collections'] ?? []);
        } catch (\Throwable $e) {
            return $none;
        }

        $isSecondaryPublication = in_array('secondary', $collections, true);

        if ($embargoDate === '') {
            $none['isSecondaryPublication'] = $isSecondaryPublication;
            return $none;
        }

        if (!$this->isCleanDate($embargoDate)) {
            return ['isEmbargoed' => true, 'embargoDate' => '', 'isSecondaryPublication' => $isSecondaryPublication];
        }

        if (!$this->isFutureDate($embargoDate)) {
            $none['isSecondaryPublication'] = $isSecondaryPublication;
            return $none;
        }

        return [
            'isEmbargoed' => true,
            'embargoDate' => $this->safelyFormatDate('d.m.Y', $embargoDate),
            'isSecondaryPublication' => $isSecondaryPublication,
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
     * "yyyy-mm-dd" or "yyyy-mm" only - strtotime() doesn't treat a bare
     * year as one, so anything else can't drive the future/past decision.
     */
    private function isCleanDate(string $date): bool
    {
        return (bool)preg_match('/^\d{4}-\d{2}(-\d{2})?$/', $date);
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
