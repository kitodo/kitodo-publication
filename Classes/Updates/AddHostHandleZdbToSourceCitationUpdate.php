<?php
namespace EWW\Dpf\Updates;

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

/**
 * #2041 (ubl-26-5048, ubl-26-5073): the embedded "Erschienen in" group of
 * blog and interview contributions is gone now, but it was the only place
 * that showed the host's Handle and ZDB-ID. Add both to the "Blog" and
 * "Erschienen in" citation rows, after the DOI line.
 */
class AddHostHandleZdbToSourceCitationUpdate extends AbstractMetadataRowUpdate
{
    private const CITATION_ROWS = ['original_in_dynamicwebresource', 'original_in_media'];

    /** field => [label, identifier type] */
    private const IDENTIFIERS = [
        'original_handle' => ['Handle', 'handle'],
        'original_zdb' => ['ZDB-ID', 'zdb'],
    ];

    public function getIdentifier(): string
    {
        return 'dpfAddHostHandleZdbToSourceCitation';
    }

    public function getTitle(): string
    {
        return 'Show host Handle and ZDB-ID in the Blog and "Erschienen in" citation rows (#2041)';
    }

    public function getDescription(): string
    {
        return 'Adds original_handle and original_zdb to tx_dpf_metadata and prints them after the DOI '
            . 'line of the Blog (original_in_dynamicwebresource) and "Erschienen in" (original_in_media) wraps.';
    }

    public function computeChanges(array $rows): array
    {
        $updates = [];
        $inserts = [];

        foreach (self::IDENTIFIERS as $indexName => [$label, $type]) {
            if (!self::hasIndexName($rows, $indexName)) {
                $inserts[] = [
                    'pid' => $this->pidOf($rows),
                    'sorting' => 37200,
                    'hidden' => 1,
                    'index_name' => $indexName,
                    'label' => 'Quellenangabe: ' . $label,
                    'format' => 1,
                    'format_type' => 'MODS',
                    'xpath' => './mods:relatedItem[@type="host"]/mods:identifier[@type="' . $type . '"]',
                    'wrap' => "key.wrap = <dt>|</dt>\r\nvalue.required = 1\r\nvalue.wrap = <dd>|</dd>",
                ];
            }
        }

        foreach (self::CITATION_ROWS as $indexName) {
            foreach (self::mainRowsByIndexName($rows, $indexName) as $uid => $row) {
                $wrap = $this->rebuildWrap((string)$row['wrap']);
                if ($wrap !== null) {
                    $updates[$uid]['wrap'] = $wrap;
                }
            }
        }

        return ['updates' => $updates, 'inserts' => $inserts];
    }

    /** @return string|null the new wrap, or null if there is no DOI block or the lines exist already */
    public function rebuildWrap(string $wrap): ?string
    {
        $parsed = self::parseWrapChain($wrap);
        if ($parsed === null) {
            return null;
        }
        [$preamble, $blocks] = $parsed;

        $fields = array_map([self::class, 'blockField'], $blocks);
        $doi = array_search('original_doi', $fields, true);
        if ($doi === false || in_array('original_handle', $fields, true)) {
            return null;
        }

        $new = [];
        foreach (self::IDENTIFIERS as $field => [$label]) {
            $new[] = self::labelledFieldBlock($field, $label);
        }
        array_splice($blocks, $doi + 1, 0, $new);

        return self::renderWrapChain($preamble, $blocks);
    }

    private function pidOf(array $rows): int
    {
        foreach (self::mainRowsByIndexName($rows, 'original_publisher') as $row) {
            return (int)$row['pid'];
        }

        return (int)($rows[0]['pid'] ?? 1);
    }
}
