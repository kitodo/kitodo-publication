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
 * Fixes two tx_dpf_metadata rows found empty during UBL landing page QA
 * (#2039): "place_of_publication" and "place_of_publication_host" (both
 * "Erscheinungsort" fields under "Erstveröffentlichung"/"Quellenangabe")
 * have correct xpaths but an empty `wrap` column, so the extracted value
 * is never rendered — Verlagsort silently disappears while the sibling
 * fields (Verlag, Jahr, DOI) on the same rows work fine.
 *
 * Sets `wrap` to the same standalone-row pattern already used by the
 * working "original_place" row (uid 300).
 */
class FixMissingPlaceOfPublicationWrapUpdate implements UpgradeWizardInterface
{
    private const AFFECTED_INDEX_NAMES = ['place_of_publication', 'place_of_publication_host'];

    private const WRAP = "key.wrap = <dt>|</dt>\r\nvalue.required = 1\r\nvalue.wrap = <dd>|</dd>";

    public function getIdentifier(): string
    {
        return 'dpfFixMissingPlaceOfPublicationWrap';
    }

    public function getTitle(): string
    {
        return 'Fix missing wrap on place_of_publication metadata rows';
    }

    public function getDescription(): string
    {
        return 'Sets `wrap` on the "place_of_publication" and "place_of_publication_host" '
            . 'tx_dpf_metadata rows, which had a correct xpath but an empty wrap and so '
            . 'never rendered Verlagsort on the landing page.';
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
     * @param array $row of [uid, index_name, wrap]
     * @return array
     */
    public function computeFix(array $row): array
    {
        if (!in_array($row['index_name'], self::AFFECTED_INDEX_NAMES, true)) {
            return [];
        }
        if (trim((string) $row['wrap']) !== '') {
            return [];
        }

        return ['wrap' => self::WRAP];
    }

    protected function findAffectedRows(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        return $connection->executeQuery(
            'SELECT uid, index_name, wrap FROM tx_dpf_metadata WHERE deleted = 0 AND index_name IN (?, ?)',
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
