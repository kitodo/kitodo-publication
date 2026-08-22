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
 * Fixes a malformed "Andere Ausgabe" link found while sweeping #2039
 * (qucosa-82985): the "otherVersion_local1" row (uid 203) is missing the
 * `[1]` position filter its sibling "otherVersion_local2" (uid 498, xpath
 * `.../mods:relatedItem[@type="otherversion"][2]/...`) has, so it matches
 * the identifier[@type="local"] under *every* otherversion relatedItem, not
 * just the first. For a record with two otherversion entries this yields
 * two values, which getMetadataHtml() joins with ", " before using the
 * string as a typolink parameter - producing an href with two raw internal
 * Fedora REST URLs comma-joined together (confirmed live: qucosa-82985).
 */
class FixOtherVersionLocal1PositionUpdate implements UpgradeWizardInterface
{
    private const AFFECTED_INDEX_NAME = 'otherVersion_local1';

    private const OLD_XPATH = './mods:relatedItem[@type="otherversion"]/mods:identifier[@type="local"]';

    private const NEW_XPATH = './mods:relatedItem[@type="otherversion"][1]/mods:identifier[@type="local"]';

    public function getIdentifier(): string
    {
        return 'dpfFixOtherVersionLocal1Position';
    }

    public function getTitle(): string
    {
        return 'Add missing [1] position filter to otherVersion_local1 xpath';
    }

    public function getDescription(): string
    {
        return 'Restricts the "otherVersion_local1" tx_dpf_metadata row\'s xpath to the first '
            . 'otherversion relatedItem, matching its numbered siblings (otherVersion_local2..5). '
            . 'Without it, a record with more than one otherversion entry gets both local '
            . 'identifiers joined into one malformed "Andere Ausgabe" link (#2039).';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * @param array $row of [uid, index_name, xpath]
     * @return array column/value pairs to update, or [] if not affected
     */
    public function computeFix(array $row): array
    {
        if ($row['index_name'] !== self::AFFECTED_INDEX_NAME) {
            return [];
        }
        if (trim((string) $row['xpath']) !== self::OLD_XPATH) {
            return [];
        }

        return ['xpath' => self::NEW_XPATH];
    }

    protected function findAffectedRows(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        return $connection->executeQuery(
            'SELECT uid, index_name, xpath FROM tx_dpf_metadata WHERE deleted = 0 AND index_name = ?',
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
