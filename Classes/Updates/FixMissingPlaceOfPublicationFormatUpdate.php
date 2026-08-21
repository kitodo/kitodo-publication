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
 * FixMissingPlaceOfPublicationWrapUpdate (#2039) set `wrap` on
 * "place_of_publication"/"place_of_publication_host" but Erscheinungsort
 * still never rendered live (confirmed on qucosa-33168): both rows have
 * `format = 0`, `format_type = ''`, while every sibling MODS-extracted row
 * has `format = 1`, `format_type = 'MODS'`. MetadataMappingRepository::
 * findExtractionRules() only selects rows with `format > 0 AND format_type
 * = ?`, so these two rows were silently excluded from extraction entirely —
 * their xpath and wrap were both correct but never even evaluated.
 */
class FixMissingPlaceOfPublicationFormatUpdate implements UpgradeWizardInterface
{
    private const AFFECTED_INDEX_NAMES = ['place_of_publication', 'place_of_publication_host'];

    public function getIdentifier(): string
    {
        return 'dpfFixMissingPlaceOfPublicationFormat';
    }

    public function getTitle(): string
    {
        return 'Fix missing format/format_type on place_of_publication metadata rows';
    }

    public function getDescription(): string
    {
        return 'Sets `format = 1` and `format_type = "MODS"` on the "place_of_publication" and '
            . '"place_of_publication_host" tx_dpf_metadata rows, which had correct xpath/wrap but '
            . 'were excluded from extraction entirely because format was 0.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * Given a tx_dpf_metadata row, returns the column/value pairs it needs
     * updated, or [] if the row is already fine / not affected.
     *
     * @param array $row of [uid, index_name, format, format_type]
     * @return array
     */
    public function computeFix(array $row): array
    {
        if (!in_array($row['index_name'], self::AFFECTED_INDEX_NAMES, true)) {
            return [];
        }
        if ((int) $row['format'] === 1 && $row['format_type'] === 'MODS') {
            return [];
        }

        return ['format' => 1, 'format_type' => 'MODS'];
    }

    protected function findAffectedRows(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        return $connection->executeQuery(
            'SELECT uid, index_name, format, format_type FROM tx_dpf_metadata WHERE deleted = 0 AND index_name IN (?, ?)',
            self::AFFECTED_INDEX_NAMES
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
