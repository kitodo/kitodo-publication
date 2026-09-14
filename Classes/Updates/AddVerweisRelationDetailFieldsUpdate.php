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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Fixes #2047's "Verweis" row (`UBL-26-5088`): unlike the other relation
 * types (fixed by AddRelationDetailFieldsUpdate/dpfAddRelationDetailFields),
 * "Verweis" (mods:relatedItem[@type="references"], 10-slot positional) was
 * believed complete (Titel/Bemerkung/DOI/URL only) - Leipzig's live re-test
 * confirmed URN/Handle/ISBN/ISSN/ZDB-ID/Bandzählung/Heftzählung are still
 * missing. Ground truth confirmed against the real METS for `ubl-26-5088`
 * (fetched via the metsdisseminator, not the raw Fedora datastream - see
 * feedback_metsdisseminator_ground_truth): every new field lives directly
 * under the same relatedItem as the existing Titel/Bemerkung/DOI/URL ones,
 * `mods:identifier[@type="urn|handle|isbn|issn|zdb"]` and
 * `mods:part[@type="volume"|"issue"]/mods:detail/mods:number` (Heftzählung
 * has no live example in this fixture, but mirrors Bandzählung's confirmed
 * shape exactly - same field, different @type value; the ticket's own "nur
 * bei Sonderheft/Zeitschriftenheft" caveat is data-driven, not a code
 * condition - the field is simply empty for every other relation).
 *
 * New rows are inserted hidden=1 from the start (splice-only, never
 * rendered standalone).
 *
 * Existing gate positions 10/20/30/40 (Titel/Bemerkung/DOI/URL - confirmed
 * identical across all 10 slots) and content positions 10-40 are left
 * untouched; new fields are appended at positions 50-110, per the ticket's
 * own field order (URN/Handle/ISBN/ISSN/ZDB-ID/Bandzählung/Heftzählung).
 *
 * Each of the 10 slots also carries a pre-existing broken line
 * (`50. - TEXT` / `50.field = ISBN`, invalid TypoScript, inert) - a leftover
 * from an earlier abandoned attempt at this exact ticket item. Removed here
 * since it occupies the same position (50) this fix now uses; not otherwise
 * in scope. NOT fixed as part of this wizard (separate, unrelated,
 * pre-existing): slot 5's `url_references5` field mistakenly reads
 * `field = url_references1` (copy-paste bug, same class as
 * FixOtherVersionLocal1PositionUpdate) - flagged for its own follow-up.
 *
 * Idempotent: does nothing if references_urn_1 already exists (new fields)
 * or if a given record's wrap already contains position 50 (composite
 * wrap) - each of the two checks is independent per side effect.
 */
class AddVerweisRelationDetailFieldsUpdate implements UpgradeWizardInterface
{
    private const STANDALONE_WRAP = "key.wrap = <dt>|</dt>\r\nvalue.required = 1\r\nvalue.wrap = <dd>|</dd>";

    private const SLOT_COUNT = 10;

    /** [index_name suffix, mods sub-path, display label suffix] */
    private const NEW_SUB_FIELDS = [
        ['urn', 'mods:identifier[@type="urn"]', 'URN'],
        ['handle', 'mods:identifier[@type="handle"]', 'Handle'],
        ['isbn', 'mods:identifier[@type="isbn"]', 'ISBN'],
        ['issn', 'mods:identifier[@type="issn"]', 'ISSN'],
        ['zdb', 'mods:identifier[@type="zdb"]', 'ZDB-ID'],
        ['volume', 'mods:part[@type="volume"]/mods:detail/mods:number', 'Bandzählung'],
        ['issue', 'mods:part[@type="issue"]/mods:detail/mods:number', 'Heftzählung'],
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getNewRows(): array
    {
        $rows = [];
        $sorting = 200000;
        for ($slot = 1; $slot <= self::SLOT_COUNT; $slot++) {
            foreach (self::NEW_SUB_FIELDS as [$suffix, $subPath, $labelSuffix]) {
                $rows[] = [
                    'pid' => 1,
                    'sorting' => $sorting,
                    'index_name' => 'references_' . $suffix . '_' . $slot,
                    'label' => $slot . '. Verweis - ' . $labelSuffix,
                    'format' => 1,
                    'format_type' => 'MODS',
                    'hidden' => 1,
                    'xpath' => './mods:relatedItem[@type="references" and not(mods:typeOfResource)][' . $slot . ']/' . $subPath,
                    'wrap' => self::STANDALONE_WRAP,
                ];
                $sorting += 4;
            }
        }

        return $rows;
    }

    public function getIdentifier(): string
    {
        return 'dpfAddVerweisRelationDetailFields';
    }

    public function getTitle(): string
    {
        return 'Add URN/Handle/ISBN/ISSN/ZDB-ID/Bandzählung/Heftzählung rows and wrap splices to Verweis';
    }

    public function getDescription(): string
    {
        return 'Adds the missing "Verweis" sub-fields (URN/Handle/ISBN/ISSN/ZDB-ID/Bandzählung/Heftzählung) '
            . 'per #2047 (UBL-26-5088) and splices them into the existing Titel/Bemerkung/DOI/URL composite '
            . 'wrap (uid 201/202), both German and English overlay. Also strips a pre-existing broken '
            . 'TypoScript remnant line that occupied the same position.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        return $this->countByIndexName($connection, 'references_urn_1') === 0;
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        foreach ($this->getNewRows() as $row) {
            if ($this->countByIndexName($connection, $row['index_name']) === 0) {
                $connection->insert('tx_dpf_metadata', $row);
            }
        }

        foreach ([202, 201] as $uid) {
            $currentWrap = (string) $connection->executeQuery(
                'SELECT wrap FROM tx_dpf_metadata WHERE uid = ?',
                [$uid]
            )->fetchColumn(0);

            if ($currentWrap === '' || strpos($currentWrap, 'references_urn_1') !== false) {
                continue;
            }

            $connection->update(
                'tx_dpf_metadata',
                ['wrap' => $this->extendWrap($currentWrap)],
                ['uid' => $uid]
            );
        }

        return true;
    }

    /**
     * Strips the pre-existing broken remnant line, then appends positions
     * 50-110 (the 7 new sub-fields) to each of the SLOT_COUNT gate blocks
     * (anchored on the always-present `40.field = url_references{N}` line)
     * and content blocks (anchored on the Nth occurrence of the always-
     * present `wrap = <dd>|</dd>` closing line - content field order/
     * formatting otherwise varies slot to slot, so this is the only
     * reliably unique per-slot anchor).
     */
    public function extendWrap(string $wrap): string
    {
        $wrap = (string) preg_replace(
            '/[ \t]*50\.\s*-\s*TEXT\r?\n[ \t]*50\.field\s*=\s*ISBN[ \t]*\r?\n/',
            '',
            $wrap
        );

        for ($slot = 1; $slot <= self::SLOT_COUNT; $slot++) {
            $wrap = $this->appendGateFields($wrap, $slot);
        }

        $searchOffset = 0;
        for ($slot = 1; $slot <= self::SLOT_COUNT; $slot++) {
            [$wrap, $searchOffset] = $this->appendContentFields($wrap, $slot, $searchOffset);
        }

        return $wrap;
    }

    private function appendGateFields(string $wrap, int $slot): string
    {
        $anchor = "40.field = url_references{$slot}\r\n";
        $pos = strpos($wrap, $anchor);
        if ($pos === false) {
            return $wrap;
        }
        $insertAt = $pos + strlen($anchor);

        $lines = '';
        $position = 50;
        foreach (self::NEW_SUB_FIELDS as [$suffix]) {
            $lines .= "\t\t\t{$position} = TEXT\r\n\t\t\t{$position}.field = references_{$suffix}_{$slot}\r\n";
            $position += 10;
        }

        return substr($wrap, 0, $insertAt) . $lines . substr($wrap, $insertAt);
    }

    /**
     * @return array{0: string, 1: int} the updated wrap and the offset to
     *   resume searching from for the next slot
     */
    private function appendContentFields(string $wrap, int $slot, int $searchOffset): array
    {
        $closingMarker = 'wrap = <dd>|</dd>';
        $pos = strpos($wrap, $closingMarker, $searchOffset);
        if ($pos === false) {
            return [$wrap, $searchOffset];
        }

        $lines = '';
        $position = 50;
        foreach (self::NEW_SUB_FIELDS as [$suffix, , $labelSuffix]) {
            $lines .= "\t{$position} = TEXT\r\n\t{$position} {\r\n\t\tfield = references_{$suffix}_{$slot}\r\n\t\trequired = 1\r\n\t\twrap = {$labelSuffix}:&nbsp;| <br />\r\n\t}\r\n";
            $position += 10;
        }

        $wrap = substr($wrap, 0, $pos) . $lines . substr($wrap, $pos);
        $nextOffset = $pos + strlen($lines) + strlen($closingMarker);

        return [$wrap, $nextOffset];
    }

    /**
     * Counts non-deleted rows for one index_name via a raw query, not
     * Connection::count() - tx_dpf_metadata's TCA declares hidden as its
     * enablecolumns.disabled field, so Connection::count()'s QueryBuilder
     * silently applies a HiddenRestriction on top of the caller's own WHERE,
     * see feedback_typo3_connection_count_hidden_restriction.
     */
    private function countByIndexName(Connection $connection, string $indexName): int
    {
        return (int) $connection->executeQuery(
            'SELECT COUNT(*) FROM tx_dpf_metadata WHERE deleted = 0 AND index_name = ?',
            [$indexName]
        )->fetchColumn(0);
    }
}
