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
 * Fixes #2039 red item "Forschungsdatenverweis" (Buch, qucosa-80365): the
 * label renders but the link doesn't. The row that actually renders this
 * block is "researchData1" (uid 127) - a composite TypoScript COA that
 * assembles up to 4 research-data entries from the (individually hidden)
 * researchData_title/doi/urlN extraction rows.
 *
 * Items 2-4's link line correctly reads `field = researchData_url2/3/4`.
 * Item 1's link line has a typo: `field = researchData_url` (missing the
 * "1" suffix) - a field that doesn't exist, so `required = 1` always blanks
 * it, and the link text/href for the first (and often only) research-data
 * entry never renders, even though the underlying data extracts fine
 * (confirmed against the live metsdisseminator output for qucosa-80365).
 */
class FixResearchDataUrl1LinkTypoUpdate implements UpgradeWizardInterface
{
    private const AFFECTED_INDEX_NAME = 'researchData1';

    private const OLD_FIELD = "\t30 {\n\t\tfield = researchData_url\n\t\trequired = 1\n"
        . "\t\ttypolink.parameter.field = researchData_url1\n\t\twrap = Link:&nbsp;|<br />\n\t}";

    private const NEW_FIELD = "\t30 {\n\t\tfield = researchData_url1\n\t\trequired = 1\n"
        . "\t\ttypolink.parameter.field = researchData_url1\n\t\twrap = Link:&nbsp;|<br />\n\t}";

    public function getIdentifier(): string
    {
        return 'dpfFixResearchDataUrl1LinkTypo';
    }

    public function getTitle(): string
    {
        return 'Fix researchData1 item-1 link field-name typo';
    }

    public function getDescription(): string
    {
        return 'The "researchData1" tx_dpf_metadata row\'s wrap displays the link text for its first '
            . 'entry from a nonexistent field ("researchData_url" instead of "researchData_url1"), so '
            . 'the link never rendered even though the underlying URL extracted fine. Items 2-4 already '
            . 'use the correctly-suffixed field name; this fixes item 1 to match (#2039).';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * @param array $row of [uid, index_name, wrap]
     * @return array
     */
    public function computeFix(array $row): array
    {
        if ($row['index_name'] !== self::AFFECTED_INDEX_NAME) {
            return [];
        }

        $wrap = (string) $row['wrap'];
        if (strpos($wrap, self::OLD_FIELD) === false) {
            return [];
        }

        return ['wrap' => str_replace(self::OLD_FIELD, self::NEW_FIELD, $wrap)];
    }

    protected function findAffectedRows(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        return $connection->executeQuery(
            'SELECT uid, index_name, wrap FROM tx_dpf_metadata WHERE deleted = 0 AND index_name = ?',
            [self::AFFECTED_INDEX_NAME]
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
