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
 * Fixes two duplicate-row items from #2041:
 *
 * - "Verlag" (uid 37, index_name=corporation_publisher) and "Publizierende
 *   Institution" (uid 36, index_name=university_publisher) share the exact
 *   same xpath (./mods:relatedItem[@type="otherversion"]/mods:originInfo/
 *   mods:publisher) - two rows for one value, rendered under two labels.
 *   Ticket wants it shown once, as "Verlag" - hide uid 36.
 * - "Erscheinungsort (Quelle)" (uid 532, index_name=place_of_publication_host)
 *   duplicates data already embedded in the Quellenangabe prose block
 *   (the "Erscheinungsort: ..." line inside e.g. the Konferenzband citation).
 *   Ticket wants it struck entirely - hide uid 532.
 */
class HideDuplicateMetadataRowsUpdate implements UpgradeWizardInterface
{
    private const INDEX_NAMES_TO_HIDE = [
        'university_publisher',
        'place_of_publication_host',
    ];

    public function getIdentifier(): string
    {
        return 'dpfHideDuplicateMetadataRows';
    }

    public function getTitle(): string
    {
        return 'Hide duplicate "Publizierende Institution" and "Erscheinungsort (Quelle)" rows (#2041)';
    }

    public function getDescription(): string
    {
        return 'Hides tx_dpf_metadata uid 36 (university_publisher, "Publizierende Institution" - same '
            . 'xpath as "Verlag") and uid 532 (place_of_publication_host, "Erscheinungsort (Quelle)" - '
            . 'already shown inside the Quellenangabe citation), so each value renders once.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * @param array $row of [index_name, hidden]
     * @return array column/value pairs to update, or [] if already hidden
     */
    public function computeFix(array $row): array
    {
        if (!in_array($row['index_name'], self::INDEX_NAMES_TO_HIDE, true)) {
            return [];
        }

        if ((int) $row['hidden'] === 1) {
            return [];
        }

        return ['hidden' => 1];
    }

    protected function findAffectedRows(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        return $connection->executeQuery(
            'SELECT uid, index_name, hidden FROM tx_dpf_metadata'
            . ' WHERE deleted = 0 AND l18n_parent = 0 AND index_name IN (?, ?)',
            self::INDEX_NAMES_TO_HIDE
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
