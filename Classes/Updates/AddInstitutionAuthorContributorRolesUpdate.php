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
 * Fixes #2047 "Institution: Rolle" (ubl-26-5018): mods:role "edt"/"oth" on a
 * corporate name already have their own display row (uid 43/12), but "aut"
 * and "ctb" never got one - both roles occur live and simply never render.
 *
 * Adds two rows mirroring the existing corporation_editor/corporation_other
 * pattern exactly (same xpath shape, same wrap). Idempotent: does nothing if
 * a corporation_author row already exists.
 */
class AddInstitutionAuthorContributorRolesUpdate implements UpgradeWizardInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getNewRows(): array
    {
        return [
        [
            'pid' => 1,
            'sorting' => 12816,
            'index_name' => 'corporation_author',
            'label' => 'AutorIn (Institution)',
            'format' => 1,
            'format_type' => 'MODS',
            'xpath' => 'mods:name[@type="corporate"][mods:role/mods:roleTerm="aut"]/mods:namePart',
            'wrap' => "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.wrap = <dd>|</dd>",
        ],
        [
            'pid' => 1,
            'sorting' => 114816,
            'index_name' => 'corporation_contributor',
            'label' => 'Beitragende/r (Institution)',
            'format' => 1,
            'format_type' => 'MODS',
            'xpath' => 'mods:name[@type="corporate"][mods:role/mods:roleTerm="ctb"]/mods:namePart',
            'wrap' => "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.wrap = <dd>|</dd>",
        ],
        ];
    }

    public function getIdentifier(): string
    {
        return 'dpfAddInstitutionAuthorContributorRoles';
    }

    public function getTitle(): string
    {
        return 'Add tx_dpf_metadata rows for Institution AutorIn/Beitragende(r) roles';
    }

    public function getDescription(): string
    {
        return 'Adds corporation_author (aut) / corporation_contributor (ctb) rows, mirroring the '
            . 'existing corporation_editor (edt) / corporation_other (oth) rows, so those two '
            . 'institution roles render on the landing page (#2047).';
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

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        $existing = $connection->count(
            'uid',
            'tx_dpf_metadata',
            ['deleted' => 0, 'index_name' => 'corporation_author']
        );

        return $existing === 0;
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        foreach ($this->getNewRows() as $row) {
            $exists = $connection->count(
                'uid',
                'tx_dpf_metadata',
                ['deleted' => 0, 'index_name' => $row['index_name']]
            );

            if ($exists === 0) {
                $connection->insert('tx_dpf_metadata', $row);
            }
        }

        return true;
    }
}
