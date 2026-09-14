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
 * Fixes #2041 (ubl-26-5023 / qucosa-94827 / qucosa-15411): uid 225
 * (otherVersion00, main) and its English overlay uid 224 (otherVersion0,
 * l18n_parent=225) render a standalone "Andere Ausgabe" title+note row
 * built entirely from otherVersionN/noteN splices, independent of the
 * otherversion_doi/otherversion_url identifier rows added by
 * AddOtherVersionIdentifierRowsUpdate.
 *
 * Leipzig's 2026-09-09 ticket comment asks for this row to disappear
 * unconditionally, even though the title/note text exists in real data
 * (ticket #1968 covers rescuing that legacy content elsewhere) - showing
 * it here contradicts how every other otherVersion field is presented.
 * Confirmed live: qucosa-94827 leaked the internal Fedora test host as
 * link text, qucosa-15411 showed a redundant free-text note.
 *
 * Nothing splices {field:otherVersion00}/{field:otherVersion0} from
 * another row (checked live DB), so hiding is safe: extraction (format=0
 * on both rows already, so nothing was ever extracted from them anyway)
 * is unaffected and no other row loses data.
 */
class HideOtherVersionTitleNoteRowUpdate implements UpgradeWizardInterface
{
    private const AFFECTED_INDEX_NAMES = ['otherVersion00', 'otherVersion0'];

    /**
     * @param array $row of [uid, index_name, hidden]
     * @return array
     */
    public function computeFix(array $row): array
    {
        if (!in_array($row['index_name'], self::AFFECTED_INDEX_NAMES, true)) {
            return [];
        }
        if ((int) $row['hidden'] === 1) {
            return [];
        }

        return ['hidden' => 1];
    }

    public function getIdentifier(): string
    {
        return 'dpfHideOtherVersionTitleNoteRow';
    }

    public function getTitle(): string
    {
        return 'Hide the standalone "Andere Ausgabe" title/note row';
    }

    public function getDescription(): string
    {
        return 'Sets hidden=1 on otherVersion00/otherVersion0 (uid 225/224) so the leftover '
            . '"Andere Ausgabe" title+note row stops rendering; DOI/Link now live in the '
            . 'promoted otherversion_doi/otherversion_url identifier rows instead (#2041).';
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
            'SELECT uid, index_name, hidden FROM tx_dpf_metadata WHERE deleted = 0 AND index_name IN ('
                . implode(',', array_fill(0, count(self::AFFECTED_INDEX_NAMES), '?'))
                . ')',
            self::AFFECTED_INDEX_NAMES
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
