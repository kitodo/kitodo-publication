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
 * Fixes #2039 red item "ISSN" (Zeitschrift/Zeitschriftenheft, qucosa-34302 /
 * qucosa-11507 / qucosa-11225): "issn" (tx_dpf_metadata uid 255) only
 * matches a document-level `mods:identifier[@type="issn"]`, but for these
 * records (confirmed against the live metsdisseminator output for
 * qucosa-34302 - not the raw Fedora datastream) the ISSN sits under
 * `relatedItem[@type="otherversion"]`, the exact same scope gap
 * FixIsbnOtherVersionScopeUpdate.php already fixed for ISBN. The other
 * candidate row, "ISSN0" (uid 270), is a dead row (blank xpath/wrap/
 * format_type) and not touched by this fix.
 */
class FixIssnOtherVersionScopeUpdate implements UpgradeWizardInterface
{
    private const AFFECTED_INDEX_NAME = 'issn';

    private const OLD_XPATH = './mods:identifier[@type="issn"]';

    private const NEW_XPATH = './mods:identifier[@type="issn"]'
        . ' | ./mods:relatedItem[@type="otherversion"]/mods:identifier[@type="issn"]';

    public function getIdentifier(): string
    {
        return 'dpfFixIssnOtherVersionScope';
    }

    public function getTitle(): string
    {
        return 'Widen issn xpath to also match relatedItem[otherversion] scope';
    }

    public function getDescription(): string
    {
        return 'Broadens the "issn" tx_dpf_metadata row\'s xpath to a union of the document-level '
            . 'identifier and the relatedItem[@type="otherversion"]-scoped one, so ISSN renders for '
            . 'Zeitschrift/Zeitschriftenheft records where it only exists in the latter scope (#2039).';
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
     * @param array $row of [uid, index_name, xpath]
     * @return array
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
