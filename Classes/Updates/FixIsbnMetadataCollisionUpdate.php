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
 * Soft-deletes two malformed l18n overlay rows in tx_dpf_metadata found
 * during UBL landing page QA (#2039):
 *
 * - an English overlay of the working `isbn0` row left with format=0 and
 *   a blank xpath (dead, never extracts anything, but still surfaces as an
 *   extra "ISBN" field for English-language rendering)
 * - an English overlay of the `eisbn` row whose xpath/label were edited to
 *   point at plain ISBN data instead of the parent's E-ISBN — since
 *   findRenderableFields() doesn't merge overlay content into its parent,
 *   this renders the E-ISBN value under an "ISBN" label for English
 *   frontend requests.
 *
 * MetadataExtractor.php is untouched — findExtractionRules() already
 * excludes l18n_parent > 0 rows, so this is a display-only fix.
 */
class FixIsbnMetadataCollisionUpdate implements UpgradeWizardInterface
{
    public function getIdentifier(): string
    {
        return 'dpfFixIsbnMetadataCollision';
    }

    public function getTitle(): string
    {
        return 'Fix ISBN metadata field collision in tx_dpf_metadata';
    }

    public function getDescription(): string
    {
        return 'Soft-deletes two malformed English l18n overlay rows in tx_dpf_metadata '
            . '(a dead blank-xpath ISBN overlay, and an eisbn overlay repurposed to show '
            . 'plain ISBN data) that caused a duplicate/mislabeled ISBN field on the '
            . 'landing page.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * A row is one of the two known-bad overlays, identified by content
     * signature (not uid) so the fix applies the same way on any
     * environment the collision was imported into.
     *
     * @param array $row of [l18n_parent, index_name, format, xpath]
     */
    public function isIsbnCollisionRow(array $row): bool
    {
        if ((int) $row['l18n_parent'] <= 0) {
            return false;
        }
        if (
            $row['index_name'] === 'ISBN'
            && (int) $row['format'] === 0
            && $row['xpath'] === ''
        ) {
            return true;
        }
        if (
            $row['index_name'] === 'eisbn'
            && $row['xpath'] === './mods:identifier[@type="isbn"]'
        ) {
            return true;
        }
        return false;
    }

    protected function findCollisionUids(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        $rows = $connection->executeQuery(
            'SELECT uid, l18n_parent, index_name, format, xpath FROM tx_dpf_metadata'
            . ' WHERE deleted = 0 AND l18n_parent > 0'
        )->fetchAll();

        return array_column(array_filter($rows, [$this, 'isIsbnCollisionRow']), 'uid');
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        return !empty($this->findCollisionUids());
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        foreach ($this->findCollisionUids() as $uid) {
            $connection->update('tx_dpf_metadata', ['deleted' => 1], ['uid' => $uid]);
        }

        return true;
    }
}
