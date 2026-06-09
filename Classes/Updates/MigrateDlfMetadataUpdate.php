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
use TYPO3\CMS\Install\Updates\RepeatableInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Migrates the landing-page metadata configuration from DLF's
 * tx_dlf_metadata (+ tx_dlf_metadataformat + tx_dlf_formats) into the
 * dpf-owned denormalized tx_dpf_metadata table.
 *
 * Original tx_dlf_metadata uids are preserved so l18n_parent chains stay
 * valid without remapping. A metadata row with more than one MODS/SLUB
 * format child yields additional rows without explicit uid.
 */
class MigrateDlfMetadataUpdate implements UpgradeWizardInterface, RepeatableInterface
{
    public function getIdentifier(): string
    {
        return 'dpfMigrateDlfMetadata';
    }

    public function getTitle(): string
    {
        return 'Migrate DLF metadata configuration to tx_dpf_metadata';
    }

    public function getDescription(): string
    {
        return 'Copies the landing-page metadata definitions (XPath rules, labels, wraps) '
            . 'from tx_dlf_metadata/tx_dlf_metadataformat/tx_dlf_formats into the '
            . 'dpf-owned tx_dpf_metadata table. Re-runnable: clears tx_dpf_metadata first.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        $schemaManager = $connection->getSchemaManager();
        if (
            !$schemaManager->tablesExist(['tx_dlf_metadata'])
            || !$schemaManager->tablesExist(['tx_dpf_metadata'])
        ) {
            return false;
        }

        $targetCount = (int) $connection->executeQuery(
            'SELECT COUNT(*) FROM tx_dpf_metadata'
        )->fetchColumn();

        return $targetCount === 0;
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        $sourceRows = $connection->executeQuery(
            'SELECT uid, pid, tstamp, crdate, cruser_id, hidden, sys_language_uid,'
            . ' l18n_parent, sorting, index_name, label, wrap, is_listed, is_sortable,'
            . ' format, default_value'
            . ' FROM tx_dlf_metadata WHERE deleted = 0 ORDER BY uid ASC'
        )->fetchAll();

        $connection->beginTransaction();
        try {
            $connection->executeStatement('DELETE FROM tx_dpf_metadata');

            $insertedCount = 0;
            foreach ($sourceRows as $sourceRow) {
                $children = $connection->executeQuery(
                    'SELECT mf.xpath, mf.xpath_sorting, f.type AS format_type'
                    . ' FROM tx_dlf_metadataformat mf'
                    . ' INNER JOIN tx_dlf_formats f ON f.uid = mf.encoded AND f.deleted = 0'
                    . ' WHERE mf.parent_id = ? AND mf.deleted = 0'
                    . ' AND f.type IN (\'MODS\', \'SLUB\')'
                    . ' ORDER BY mf.uid ASC',
                    [$sourceRow['uid']]
                )->fetchAll();

                foreach ($this->mapRow($sourceRow, $children) as $targetRow) {
                    $connection->insert('tx_dpf_metadata', $targetRow);
                    $insertedCount++;
                }
            }

            $connection->commit();
        } catch (\Throwable $exception) {
            $connection->rollBack();
            throw $exception;
        }

        return $insertedCount > 0;
    }

    /**
     * Maps one tx_dlf_metadata row (+ its MODS/SLUB format children) to one
     * or more tx_dpf_metadata insert rows.
     *
     * The first row keeps the original uid (preserves l18n_parent chains);
     * additional format children become extra rows without explicit uid.
     *
     * @param array $dlfRow
     * @param array $children rows of [xpath, xpath_sorting, format_type]
     * @return array
     */
    public function mapRow(array $dlfRow, array $children): array
    {
        $base = [
            'pid' => (int) $dlfRow['pid'],
            'tstamp' => (int) $dlfRow['tstamp'],
            'crdate' => (int) $dlfRow['crdate'],
            'cruser_id' => (int) $dlfRow['cruser_id'],
            'deleted' => 0,
            'hidden' => (int) $dlfRow['hidden'],
            'sys_language_uid' => (int) $dlfRow['sys_language_uid'],
            'l18n_parent' => (int) $dlfRow['l18n_parent'],
            'sorting' => (int) $dlfRow['sorting'],
            'index_name' => (string) $dlfRow['index_name'],
            'label' => (string) $dlfRow['label'],
            'wrap' => (string) $dlfRow['wrap'],
            'is_listed' => (int) $dlfRow['is_listed'],
            'is_sortable' => (int) $dlfRow['is_sortable'],
            'format' => (int) $dlfRow['format'],
            'format_type' => '',
            'xpath' => '',
            'xpath_sorting' => '',
            'default_value' => (string) $dlfRow['default_value'],
        ];

        $rows = [];
        $firstRow = $base;
        $firstRow['uid'] = (int) $dlfRow['uid'];
        if (!empty($children)) {
            $firstChild = array_shift($children);
            $firstRow['format_type'] = (string) $firstChild['format_type'];
            $firstRow['xpath'] = (string) $firstChild['xpath'];
            $firstRow['xpath_sorting'] = (string) $firstChild['xpath_sorting'];
        }
        $rows[] = $firstRow;

        foreach ($children as $child) {
            $extraRow = $base;
            $extraRow['format_type'] = (string) $child['format_type'];
            $extraRow['xpath'] = (string) $child['xpath'];
            $extraRow['xpath_sorting'] = (string) $child['xpath_sorting'];
            $rows[] = $extraRow;
        }

        return $rows;
    }
}
