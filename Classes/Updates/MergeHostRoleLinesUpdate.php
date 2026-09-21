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
 * #2041 (qucosa-15470, ubl-26-5007, ubl-26-5073): the source citation rows
 * "Konferenzband" (index_name original_in_proceeding0000000) and
 * "Erschienen in" (original_in_media) listed each host editor, author and
 * translator on a line of their own, and the standalone "Quellenangabe:
 * AutorIn / HerausgeberIn (Institution) ..." rows repeated the same names
 * as bold rows below. Leipzig wants one line per role inside the citation
 * row, persons first, then institutions, all separated by commas.
 *
 * The wizard adds three multi-value fields that collect every host name of
 * a role in document order (the form writes persons before institutions,
 * and it also has no 2-name limit), swaps the per-slot blocks of both
 * citation wraps for three role lines, and hides the standalone rows.
 */
class MergeHostRoleLinesUpdate extends AbstractMetadataRowUpdate
{
    private const CITATION_ROWS = ['original_in_proceeding0000000', 'original_in_media'];

    /** role field => [label, roleTerm] */
    private const ROLES = [
        'host_editors_all' => ['Herausgegeben von', 'edt'],
        'host_authors_all' => ['AutorIn', 'aut'],
        'host_translators_all' => ['ÜbersetzerIn', 'trl'],
    ];

    /** Fields printed by the per-slot blocks that the role lines replace. */
    private const SLOT_FIELDS = [
        'original_publisher', 'original_publisher_1', 'original_publisher_2',
        'original_author', 'original_author_1',
        'original_translator', 'original_translator_1',
        'original_institution_author', 'original_institution_author_1',
        'original_institution_publisher', 'original_institution_publisher_1',
    ];

    /** Standalone rows that repeat what the citation row now shows. */
    private const ROWS_TO_HIDE = [
        'original_author', 'original_author_1',
        'original_translator', 'original_translator_1',
        'original_institution_author', 'original_institution_author_1',
        'original_institution_publisher', 'original_institution_publisher_1',
    ];

    public function getIdentifier(): string
    {
        return 'dpfMergeHostRoleLines';
    }

    public function getTitle(): string
    {
        return 'Show host editors, authors and translators as one line per role (#2041)';
    }

    public function getDescription(): string
    {
        return 'Adds host_editors_all, host_authors_all and host_translators_all to tx_dpf_metadata, '
            . 'rewrites the Konferenzband and "Erschienen in" citation wraps to print one line per role, '
            . 'and hides the standalone Quellenangabe person and institution rows.';
    }

    public function computeChanges(array $rows): array
    {
        $inserts = [];
        foreach (self::ROLES as $indexName => [, $roleTerm]) {
            if (!self::hasIndexName($rows, $indexName)) {
                $inserts[] = $this->newRow($rows, $indexName, $roleTerm);
            }
        }

        return ['updates' => $this->hideUpdates($rows) + $this->wrapUpdates($rows), 'inserts' => $inserts];
    }

    /** @return array<int, array<string, mixed>> */
    private function hideUpdates(array $rows): array
    {
        $updates = [];
        foreach (self::ROWS_TO_HIDE as $indexName) {
            foreach (self::mainRowsByIndexName($rows, $indexName) as $uid => $row) {
                if ((int)$row['hidden'] !== 1) {
                    $updates[$uid] = ['hidden' => 1];
                }
            }
        }

        return $updates;
    }

    /** @return array<int, array<string, mixed>> */
    private function wrapUpdates(array $rows): array
    {
        $updates = [];
        foreach (self::CITATION_ROWS as $indexName) {
            foreach (self::mainRowsByIndexName($rows, $indexName) as $uid => $row) {
                $wrap = $this->rebuildWrap((string)$row['wrap']);
                if ($wrap !== null) {
                    $updates[$uid] = ['wrap' => $wrap];
                }
            }
        }

        return $updates;
    }

    /**
     * @return string|null the new wrap, or null if it has no block chain or is already merged
     */
    public function rebuildWrap(string $wrap): ?string
    {
        $parsed = self::parseWrapChain($wrap);
        if ($parsed === null) {
            return null;
        }
        [$preamble, $blocks] = $parsed;

        $fields = array_map([self::class, 'blockField'], $blocks);
        if (in_array('host_editors_all', $fields, true)) {
            return null;
        }

        $result = [];
        $inserted = false;
        foreach ($blocks as $i => $body) {
            if ($this->isSlotBlock($fields[$i], $body)) {
                if (!$inserted) {
                    foreach (self::ROLES as $field => [$label]) {
                        $result[] = self::labelledFieldBlock($field, $label);
                    }
                    $inserted = true;
                }
                continue;
            }
            $result[] = $body;
        }

        return $inserted ? self::renderWrapChain($preamble, $result) : null;
    }

    /** A slot block, or the closing line break gated on the first editor field. */
    private function isSlotBlock(?string $field, string $body): bool
    {
        if ($field !== null) {
            return in_array($field, self::SLOT_FIELDS, true);
        }

        return (bool)preg_match('/^\s*value\.fieldRequired = original_publisher\s*$/m', $body)
            && preg_match('/^\s*value = <br \/>\s*$/m', $body);
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function newRow(array $rows, string $indexName, string $roleTerm): array
    {
        return [
            'pid' => $this->pidOf($rows),
            'sorting' => 33300 + 8 * array_search($indexName, array_keys(self::ROLES), true),
            'hidden' => 1,
            'index_name' => $indexName,
            'label' => 'Quelle: ' . self::ROLES[$indexName][0],
            'format' => 1,
            'format_type' => 'MODS',
            'xpath' => './mods:relatedItem[@type="host"]/mods:name[mods:role/mods:roleTerm="' . $roleTerm . '"]/mods:displayForm',
            'wrap' => "key.wrap = <dt>|</dt>\r\nvalue.required = 1\r\nvalue.wrap = <dd>|</dd>",
        ];
    }

    /** The pid the Quellenangabe rows live on (all tx_dpf_metadata rows share one). */
    private function pidOf(array $rows): int
    {
        foreach (self::mainRowsByIndexName($rows, 'original_publisher') as $row) {
            return (int)$row['pid'];
        }

        return (int)($rows[0]['pid'] ?? 1);
    }
}
