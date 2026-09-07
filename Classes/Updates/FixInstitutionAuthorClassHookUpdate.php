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
 * Fixes #2040 (UBL-26-5007): "AutorIn (Institution)" (uid 547,
 * index_name=corporation_author) has no CSS class hook, while its sibling
 * "Herausgeber (Institution)" (uid 43, index_name=corporation_editor) has
 * had class="editor" since bde2f342 (#2040: add class hooks for CSS). The
 * ticket asks both roles to be styled identically - role label small/grey,
 * name large/black - but slub_web_qucosa's CSS has nothing to select the
 * AutorIn row by. Adds the matching class="author" hook here (same class
 * already used by the personal-author row) so that CSS rule applies.
 */
class FixInstitutionAuthorClassHookUpdate implements UpgradeWizardInterface
{
    private const AFFECTED_INDEX_NAME = 'corporation_author';

    private const OLD_WRAP = "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.wrap = <dd>|</dd>";
    private const NEW_WRAP = "key.wrap = <dt class=\"author\">|</dt>\nvalue.required = 1\nvalue.wrap = <dd class=\"author\">|</dd>";

    /**
     * @param array $row of [uid, index_name, wrap]
     * @return array
     */
    public function computeFix(array $row): array
    {
        if ($row['index_name'] !== self::AFFECTED_INDEX_NAME) {
            return [];
        }
        if (trim((string) $row['wrap']) !== self::OLD_WRAP) {
            return [];
        }

        return ['wrap' => self::NEW_WRAP];
    }

    public function getIdentifier(): string
    {
        return 'dpfFixInstitutionAuthorClassHook';
    }

    public function getTitle(): string
    {
        return 'Add the missing "author" CSS class hook to the AutorIn (Institution) row';
    }

    public function getDescription(): string
    {
        return 'Adds class="author" to the corporation_author row\'s <dt>/<dd> wrap, matching the '
            . 'class="editor" hook corporation_editor already has, so both institution roles can be '
            . 'styled the same way (#2040, UBL-26-5007).';
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
