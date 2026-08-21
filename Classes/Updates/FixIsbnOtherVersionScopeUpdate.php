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
 * Fixes #2039 item 5's ISBN half: "isbn0" (tx_dpf_metadata uid 272) only
 * matches a document-level `mods:identifier[@type="isbn"]`, but for
 * multivolume_work records (e.g. qucosa-33168, confirmed against the live
 * metsdisseminator output — not the raw Fedora datastream, which is a
 * different, non-authoritative representation) the ISBN sits under
 * `relatedItem[@type="otherversion"]`, the same scope place_of_publication
 * already reads. Broadens the xpath to a union of both locations so
 * existing top-level ISBN extraction (if any) is unaffected.
 */
class FixIsbnOtherVersionScopeUpdate implements UpgradeWizardInterface
{
    private const AFFECTED_INDEX_NAME = 'isbn0';

    private const OLD_XPATH = './mods:identifier[@type="isbn"]';

    private const NEW_XPATH = './mods:identifier[@type="isbn"]'
        . ' | ./mods:relatedItem[@type="otherversion"]/mods:identifier[@type="isbn"]';

    public function getIdentifier(): string
    {
        return 'dpfFixIsbnOtherVersionScope';
    }

    public function getTitle(): string
    {
        return 'Widen isbn0 xpath to also match relatedItem[otherversion] scope';
    }

    public function getDescription(): string
    {
        return 'Broadens the "isbn0" tx_dpf_metadata row\'s xpath to a union of the document-level '
            . 'identifier and the relatedItem[@type="otherversion"]-scoped one, so ISBN renders for '
            . 'multivolume_work records where it only exists in the latter scope (#2039).';
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
