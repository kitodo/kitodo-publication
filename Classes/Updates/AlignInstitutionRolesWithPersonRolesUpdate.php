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
 * Fixes #2051 (ubl-26-5023): "Sonstige beteiligte Institution" (index_name
 * corporation_other) sorts after the funding rows. It moves directly after
 * "Sonstige/r" (other). The institution rows "Beitragende/r (Institution)"
 * (corporation_contributor) and "Sonstige beteiligte Institution" also get
 * the same bullet list markup as the person rows.
 */
class AlignInstitutionRolesWithPersonRolesUpdate implements UpgradeWizardInterface
{
    private const OTHER_INSTITUTION_INDEX_NAME = 'corporation_other';
    private const OTHER_INDEX_NAME = 'other';
    private const BULLETED_INDEX_NAMES = ['corporation_contributor', 'corporation_other'];

    private const PLAIN_VALUE_WRAP = 'value.wrap = <dd>|</dd>';
    private const BULLET_VALUE_WRAP = 'value.wrap = <dd><ul><li>|</li></ul></dd>';

    public function getIdentifier(): string
    {
        return 'dpfAlignInstitutionRolesWithPersonRoles';
    }

    public function getTitle(): string
    {
        return 'Align "Sonstige beteiligte Institution" and "Beitragende/r (Institution)" with the person roles (#2051)';
    }

    public function getDescription(): string
    {
        return 'Moves "corporation_other" (Sonstige beteiligte Institution) to sort directly after '
            . '"other" (Sonstige/r). Wraps the values of "corporation_other" and "corporation_contributor" '
            . 'in a bullet list, like the person roles.';
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
            ->getConnectionForTable('tx_dpf_metadata');
    }

    private function fetchRow(string $indexName): ?array
    {
        $row = $this->getConnection()->executeQuery(
            'SELECT uid, sorting, wrap FROM tx_dpf_metadata WHERE hidden = 0 AND index_name = ?',
            [$indexName]
        )->fetch();

        return $row ?: null;
    }

    /**
     * @return int|null new sorting value for "corporation_other", or null if no move is needed
     */
    public function computeNewSorting(int $institutionSorting, int $otherSorting): ?int
    {
        $target = $otherSorting + 1;

        return $institutionSorting === $target ? null : $target;
    }

    /**
     * The wrap text has LF or CRLF line ends, depending on how the row was saved.
     *
     * @return string|null the wrap with a bulleted value.wrap, or null if it has no plain one
     */
    public function bulletWrap(string $wrap): ?string
    {
        $bulleted = preg_replace(
            '/^' . preg_quote(self::PLAIN_VALUE_WRAP, '/') . '[ \t]*$/m',
            self::BULLET_VALUE_WRAP,
            $wrap,
            -1,
            $count
        );

        return $count > 0 ? $bulleted : null;
    }

    private function pendingUpdates(): array
    {
        $updates = [];

        $institution = $this->fetchRow(self::OTHER_INSTITUTION_INDEX_NAME);
        $other = $this->fetchRow(self::OTHER_INDEX_NAME);
        if ($institution !== null && $other !== null) {
            $newSorting = $this->computeNewSorting((int) $institution['sorting'], (int) $other['sorting']);
            if ($newSorting !== null) {
                $updates[$institution['uid']]['sorting'] = $newSorting;
            }
        }

        foreach (self::BULLETED_INDEX_NAMES as $indexName) {
            $row = $this->fetchRow($indexName);
            if ($row === null) {
                continue;
            }
            $newWrap = $this->bulletWrap((string) $row['wrap']);
            if ($newWrap !== null) {
                $updates[$row['uid']]['wrap'] = $newWrap;
            }
        }

        return $updates;
    }

    public function updateNecessary(): bool
    {
        if (!$this->getConnection()->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        return !empty($this->pendingUpdates());
    }

    public function executeUpdate(): bool
    {
        foreach ($this->pendingUpdates() as $uid => $fields) {
            $this->getConnection()->update('tx_dpf_metadata', $fields, ['uid' => $uid]);
        }

        return true;
    }
}
