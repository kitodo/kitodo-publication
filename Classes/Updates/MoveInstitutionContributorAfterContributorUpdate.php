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
 * Fixes #2047 (ubl-26-5018): "Beitragende/r (Institution)" (index_name
 * corporation_contributor) sorts far after "Beitragende/r" (contributor) and
 * its 12 positional slots - Leipzig asked for it to move directly after
 * "Beitragende/r" instead, same "right after" pattern already used for
 * #2040's Erscheinungsort/Verlag reorder.
 */
class MoveInstitutionContributorAfterContributorUpdate implements UpgradeWizardInterface
{
    private const CONTRIBUTOR_INSTITUTION_INDEX_NAME = 'corporation_contributor';
    private const CONTRIBUTOR_INDEX_NAME = 'contributor';

    public function getIdentifier(): string
    {
        return 'dpfMoveInstitutionContributorAfterContributor';
    }

    public function getTitle(): string
    {
        return 'Sort "Beitragende/r (Institution)" right after "Beitragende/r" (#2047)';
    }

    public function getDescription(): string
    {
        return 'Moves "corporation_contributor" (Beitragende/r (Institution)) to sort directly after '
            . '"contributor" (Beitragende/r), instead of far below its 12 positional slots.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    private function getConnection()
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');
    }

    private function fetchSorting(string $indexName): ?array
    {
        $row = $this->getConnection()->executeQuery(
            'SELECT uid, sorting FROM tx_dpf_metadata WHERE hidden = 0 AND index_name = ?',
            [$indexName]
        )->fetch();

        return $row ?: null;
    }

    /**
     * @return int|null new sorting value for "corporation_contributor", or null if no move is needed
     */
    public function computeNewSorting(int $institutionSorting, int $contributorSorting): ?int
    {
        $target = $contributorSorting + 1;

        return $institutionSorting === $target ? null : $target;
    }

    public function updateNecessary(): bool
    {
        $connection = $this->getConnection();

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        $institution = $this->fetchSorting(self::CONTRIBUTOR_INSTITUTION_INDEX_NAME);
        $contributor = $this->fetchSorting(self::CONTRIBUTOR_INDEX_NAME);

        if ($institution === null || $contributor === null) {
            return false;
        }

        return $this->computeNewSorting((int) $institution['sorting'], (int) $contributor['sorting']) !== null;
    }

    public function executeUpdate(): bool
    {
        $institution = $this->fetchSorting(self::CONTRIBUTOR_INSTITUTION_INDEX_NAME);
        $contributor = $this->fetchSorting(self::CONTRIBUTOR_INDEX_NAME);

        if ($institution === null || $contributor === null) {
            return true;
        }

        $newSorting = $this->computeNewSorting((int) $institution['sorting'], (int) $contributor['sorting']);
        if ($newSorting === null) {
            return true;
        }

        $this->getConnection()->update(
            'tx_dpf_metadata',
            ['sorting' => $newSorting],
            ['uid' => $institution['uid']]
        );

        return true;
    }
}
