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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Fixes #2041's "Erschienen in"/"Schriftenreihe" duplicate rows.
 *
 * LandingPageAssembler::HOST_PLACEHOLDER_INDEX_NAMES and
 * ::SERIES_PLACEHOLDER_INDEX_NAME document these 6 tx_dpf_metadata rows as
 * "dead placeholders, empty xpath, kept only for their sorting value" - the
 * splice logic in getMetadataHtml() only embeds the Assembler's ES-resolved,
 * correctly-linked host/series item when the placeholder row itself renders
 * empty. That assumption no longer holds: all 6 rows' `wrap` (not their
 * empty `xpath` column) contains `value.dataWrap = {field:multivolume_title}`
 * / `{field:series_title}` - a TypoScript field-marker substitution pulling
 * a sibling row's already-resolved value, independent of xpath being blank.
 * When that sibling field has data, the placeholder row renders it directly
 * (sometimes unlinked) *and* the splice-embed still lands separately at the
 * end of the list (its "already embedded" flag never gets set, since that
 * only happens on the empty-value branch) - the same item shown twice.
 *
 * Verified empirically against 24 real documents (5 doctypes: monograph,
 * doctoral_thesis, proceeding, lecture, issue): every case with 2 rendered
 * rows had identical content in both (one linked, one not) - never a case
 * where the direct-xpath row was the sole/richer source. Safe to strip the
 * value-producing lines back to a true empty placeholder in every case
 * checked; not exhaustively verified across the whole corpus.
 *
 * Keeps only `key.wrap` on each row, dropping every `value.*` line
 * including `value.wrap3` - restores the "always empty, splice-only"
 * behaviour the Assembler's code comment already describes. `value.wrap3`
 * must go too: TYPO3 stdWrap's wrap/wrap2/wrap3 apply unconditionally, so
 * even a `value.wrap3 = <dd>|</dd>` alone turns an empty value into the
 * non-empty string "<dd></dd>" - `!empty($parsedValue)` then true, so
 * getMetadataHtml() renders a visible-but-empty row instead of falling
 * through to the splice `elseif` (caught live: an in-between version of
 * this wizard that kept value.wrap3 produced 4 empty "Erschienen in" rows
 * per document instead of fixing the duplicate).
 */
class RestoreHostSeriesPlaceholderRowsUpdate implements UpgradeWizardInterface
{
    private const INDEX_NAMES = [
        'multivolume_issue00',
        'multivolume_monograph0',
        'multivolume_doctoral_thesis',
        'multivolume_proceeding',
        'multivolume_lecture0',
        'series0',
    ];

    public function getIdentifier(): string
    {
        return 'dpfRestoreHostSeriesPlaceholderRows';
    }

    public function getTitle(): string
    {
        return 'Restore empty host/series placeholder rows to fix duplicate Erschienen in / Schriftenreihe (#2041)';
    }

    public function getDescription(): string
    {
        return 'Strips the value-producing TypoScript from 6 tx_dpf_metadata rows '
            . '(' . implode(', ', self::INDEX_NAMES) . ') back to true empty placeholders, '
            . 'so LandingPageAssembler\'s host/series splice is the sole renderer instead of '
            . 'showing the same item twice (once unlinked via the row\'s own value, once linked '
            . 'via the splice).';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * @param array $row of [index_name, wrap]
     * @return array column/value pairs to update, or [] if not applicable / already empty
     */
    public function computeFix(array $row): array
    {
        if (!in_array($row['index_name'], self::INDEX_NAMES, true)) {
            return [];
        }

        $wrap = (string) $row['wrap'];

        $keyWrapLine = null;
        foreach (preg_split('/\r\n/', $wrap) as $line) {
            if (strpos($line, 'key.wrap') === 0) {
                $keyWrapLine = $line;
                break;
            }
        }
        if ($keyWrapLine === null) {
            return [];
        }

        // Already reduced to exactly key.wrap, nothing else - done.
        if ($wrap === $keyWrapLine) {
            return [];
        }

        $fixed = $keyWrapLine;

        return ['wrap' => $fixed];
    }

    protected function findAffectedRows(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        return $connection->executeQuery(
            'SELECT uid, index_name, wrap FROM tx_dpf_metadata'
            . ' WHERE deleted = 0 AND l18n_parent = 0 AND index_name IN (?, ?, ?, ?, ?, ?)',
            self::INDEX_NAMES
        )->fetchAll();
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        foreach ($this->findAffectedRows() as $row) {
            if (!empty($this->computeFix($row))) {
                return true;
            }
        }

        return false;
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        foreach ($this->findAffectedRows() as $row) {
            $fix = $this->computeFix($row);
            if (!empty($fix)) {
                $connection->update('tx_dpf_metadata', $fix, ['uid' => $row['uid']]);
            }
        }

        return true;
    }
}
