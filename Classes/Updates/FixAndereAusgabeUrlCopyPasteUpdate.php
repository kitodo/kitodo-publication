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
 * Fixes a copy-paste bug in the "Andere Ausgabe" row (tx_dpf_metadata uid
 * 225, index_name=otherVersion00), found while investigating #2041's
 * "Andere Ausgabe: DOI und Link" item: slot 2 of its 5-slot COA structure
 * displays field `url_1` (slot 1's URL) while linking `url_2` (its own
 * URL) - `field` and `typolink.parameter.field` disagree. Every document
 * with 2+ "otherversion" relatedItems shows slot 1's URL text twice, once
 * pointing at the wrong target.
 *
 * Only this one substring is touched; the rest of the row (including the
 * broader "promote DOI/URL, drop the heading" redesign the ticket also
 * asks for) is out of scope here - the note/otherVersion-title content is
 * populated on the majority of real documents that use this row, so
 * dropping it needs an explicit product decision, not a silent code fix.
 */
class FixAndereAusgabeUrlCopyPasteUpdate implements UpgradeWizardInterface
{
    private const INDEX_NAME = 'otherVersion00';

    private const BUGGY = "\tfield = url_1\r\n\t\ttypolink.parameter.field = url_2";
    private const FIXED = "\tfield = url_2\r\n\t\ttypolink.parameter.field = url_2";

    public function getIdentifier(): string
    {
        return 'dpfFixAndereAusgabeUrlCopyPaste';
    }

    public function getTitle(): string
    {
        return 'Fix slot-2 URL copy-paste bug in the "Andere Ausgabe" row (#2041)';
    }

    public function getDescription(): string
    {
        return 'tx_dpf_metadata uid 225 (otherVersion00) displays field url_1 in its 2nd "otherversion" '
            . 'slot while linking url_2 - shows the 1st edition\'s URL text twice, one of them pointing '
            . 'at the 2nd edition\'s target. Corrects the display field to match the link.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * @param array $row of [index_name, wrap]
     * @return array column/value pairs to update, or [] if not applicable / already fixed
     */
    public function computeFix(array $row): array
    {
        if ($row['index_name'] !== self::INDEX_NAME) {
            return [];
        }

        $wrap = (string) $row['wrap'];
        if (strpos($wrap, self::BUGGY) === false) {
            return [];
        }

        return ['wrap' => str_replace(self::BUGGY, self::FIXED, $wrap)];
    }

    protected function findAffectedRows(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        return $connection->executeQuery(
            'SELECT uid, index_name, wrap FROM tx_dpf_metadata WHERE deleted = 0 AND index_name = ?',
            [self::INDEX_NAME]
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
