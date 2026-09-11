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
 * Fixes #2048: the "Forschungsbericht / Report" entry ("report") sorts
 * before "Monographie" ("monograph") in the search-form Publikationstyp
 * dropdown (tx_dpf_domain_model_documenttype.sorting drives the order).
 * User decision: it should sort right after "Monographie" instead.
 *
 * Idempotent: only moves "report" if its sorting is not already greater
 * than "monograph"'s.
 */
class ReorderReportAfterMonographInDropdownUpdate implements UpgradeWizardInterface
{
    private const REPORT_NAME = 'report';
    private const MONOGRAPH_NAME = 'monograph';

    public function getIdentifier(): string
    {
        return 'dpfReorderReportAfterMonographInDropdown';
    }

    public function getTitle(): string
    {
        return 'Sort "Forschungsbericht / Report" after "Monographie" in the Publikationstyp dropdown';
    }

    public function getDescription(): string
    {
        return 'Moves the "report" document type\'s sorting value to just after "monograph" (#2048), '
            . 'so it appears grouped with Monographie in the search-form dropdown instead of before it.';
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
            ->getConnectionForTable('tx_dpf_domain_model_documenttype');
    }

    /**
     * @return int|null new sorting value for "report", or null if no move is needed
     */
    public function computeNewReportSorting(int $reportSorting, int $monographSorting): ?int
    {
        if ($reportSorting > $monographSorting) {
            return null;
        }

        return $monographSorting + 1;
    }

    private function fetchSorting(string $name): ?array
    {
        $row = $this->getConnection()->executeQuery(
            'SELECT uid, sorting FROM tx_dpf_domain_model_documenttype WHERE deleted = 0 AND name = ?',
            [$name]
        )->fetch();

        return $row ?: null;
    }

    public function updateNecessary(): bool
    {
        $connection = $this->getConnection();

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_domain_model_documenttype'])) {
            return false;
        }

        $report = $this->fetchSorting(self::REPORT_NAME);
        $monograph = $this->fetchSorting(self::MONOGRAPH_NAME);

        if ($report === null || $monograph === null) {
            return false;
        }

        return $this->computeNewReportSorting((int) $report['sorting'], (int) $monograph['sorting']) !== null;
    }

    public function executeUpdate(): bool
    {
        $report = $this->fetchSorting(self::REPORT_NAME);
        $monograph = $this->fetchSorting(self::MONOGRAPH_NAME);

        if ($report === null || $monograph === null) {
            return true;
        }

        $newSorting = $this->computeNewReportSorting((int) $report['sorting'], (int) $monograph['sorting']);
        if ($newSorting === null) {
            return true;
        }

        $this->getConnection()->update(
            'tx_dpf_domain_model_documenttype',
            ['sorting' => $newSorting],
            ['uid' => $report['uid']]
        );

        return true;
    }
}
