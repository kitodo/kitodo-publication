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
 * Fixes #2047 "Peer Review" (UBL-26-5088): the "peer_review" row (uid 533)
 * already has a working xpath (confirmed against the live metsdisseminator
 * output - slub:peerReview = "yes") but was left half-configured: format = 0
 * and wrap = '' (an empty string). MetadataMappingRepository::
 * findExtractionRules() only includes format > 0 rows (or format = 0 rows
 * with a default_value, which this one also lacks) - so the row is silently
 * skipped during extraction entirely, before its xpath is ever evaluated.
 *
 * Fills in format = 1 / format_type = MODS / a standard key+value wrap,
 * mirroring every other simple value row (e.g. "zdb", uid 273). Idempotent:
 * only touches the row if it's still in this half-configured state.
 */
class FixPeerReviewFieldConfigUpdate implements UpgradeWizardInterface
{
    private const AFFECTED_INDEX_NAME = 'peer_review';

    private const NEW_WRAP = "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.wrap = <dd>|</dd>";

    /**
     * @param array $row of [uid, index_name, format, wrap]
     * @return array
     */
    public function computeFix(array $row): array
    {
        if ($row['index_name'] !== self::AFFECTED_INDEX_NAME) {
            return [];
        }
        if ((int) $row['format'] > 0 && trim((string) $row['wrap']) !== '') {
            return [];
        }

        return [
            'format' => 1,
            'format_type' => 'MODS',
            'wrap' => self::NEW_WRAP,
        ];
    }

    public function getIdentifier(): string
    {
        return 'dpfFixPeerReviewFieldConfig';
    }

    public function getTitle(): string
    {
        return 'Fix the half-configured peer_review tx_dpf_metadata row';
    }

    public function getDescription(): string
    {
        return 'Sets format/format_type/wrap on the "peer_review" row so its already-correct xpath '
            . 'is actually evaluated during extraction (#2047) - format = 0 made it invisible to '
            . 'findExtractionRules() regardless of the xpath.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    protected function findAffectedRows(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        return $connection->executeQuery(
            'SELECT uid, index_name, format, wrap FROM tx_dpf_metadata WHERE deleted = 0 AND index_name = ?',
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
