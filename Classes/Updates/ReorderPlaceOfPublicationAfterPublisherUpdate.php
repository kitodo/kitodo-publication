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
 * Fixes #2040: for standalone (book/monograph-type) publications, the
 * top-level "Erscheinungsort" row (index_name "place_of_publication") sorts
 * far after "Verlag" (index_name "corporation_publisher") - other unrelated
 * rows (Sprache, URN, Veröffentlichungsdatum, ...) land between them, pushing
 * Erscheinungsort to the very end of the metadata list. User decision:
 * Erscheinungsort should sort right after Verlag.
 *
 * Idempotent: only moves it if it doesn't already sort right after Verlag.
 *
 * Note: the Quellenangabe-embedded "Erscheinungsort" for unselbstständige
 * works (index_name "original_place", uid 300) already got this same fix
 * via FixSammelbandHostLinkAndFieldOrderUpdate (#2040 item 5) - this wizard
 * covers the separate, top-level row used by standalone works only.
 */
class ReorderPlaceOfPublicationAfterPublisherUpdate implements UpgradeWizardInterface
{
    private const PLACE_INDEX_NAME = 'place_of_publication';
    private const PUBLISHER_INDEX_NAME = 'corporation_publisher';

    public function getIdentifier(): string
    {
        return 'dpfReorderPlaceOfPublicationAfterPublisher';
    }

    public function getTitle(): string
    {
        return 'Sort standalone-work "Erscheinungsort" right after "Verlag" (#2040)';
    }

    public function getDescription(): string
    {
        return 'Moves the top-level "place_of_publication" (Erscheinungsort) metadata row\'s sorting '
            . 'value to just after "corporation_publisher" (Verlag), so it no longer lands at the end '
            . 'of the landing page metadata list, behind unrelated rows.';
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
     * @return int|null new sorting value for "place_of_publication", or null if no move is needed
     */
    public function computeNewPlaceSorting(int $placeSorting, int $publisherSorting): ?int
    {
        $target = $publisherSorting + 1;

        return $placeSorting === $target ? null : $target;
    }

    public function updateNecessary(): bool
    {
        $connection = $this->getConnection();

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        $place = $this->fetchSorting(self::PLACE_INDEX_NAME);
        $publisher = $this->fetchSorting(self::PUBLISHER_INDEX_NAME);

        if ($place === null || $publisher === null) {
            return false;
        }

        return $this->computeNewPlaceSorting((int) $place['sorting'], (int) $publisher['sorting']) !== null;
    }

    public function executeUpdate(): bool
    {
        $place = $this->fetchSorting(self::PLACE_INDEX_NAME);
        $publisher = $this->fetchSorting(self::PUBLISHER_INDEX_NAME);

        if ($place === null || $publisher === null) {
            return true;
        }

        $newSorting = $this->computeNewPlaceSorting((int) $place['sorting'], (int) $publisher['sorting']);
        if ($newSorting === null) {
            return true;
        }

        $this->getConnection()->update(
            'tx_dpf_metadata',
            ['sorting' => $newSorting],
            ['uid' => $place['uid']]
        );

        return true;
    }
}
