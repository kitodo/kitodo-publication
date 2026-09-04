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
 * Adds host-scoped person/institution role fields missing from #2047's
 * Preprint and Beitrag/Interview Quellenangabe rows (`ubl-26-5070`,
 * `ubl-26-5073`): only editor (`edt` person, `original_publisher`/`_1`/`_2`)
 * existed. `aut`/`trl` person and `aut`/`edt` corporate were never
 * extracted at all, confirmed against both live METS fixtures.
 *
 * xpath pattern and position-scoping copy the fix already shipped for
 * original_publisher (#2041, FixHostEditorPositionScopeUpdate): parenthesize
 * the node-set before indexing so multiple <relatedItem type="host"> blocks
 * on the same document don't collide per-position.
 *
 * Two slots per role (0/1), matching the existing original_publisher/_1
 * two-slot precedent. Rows are consumed only via `{field:...}` splices in
 * the Preprint/Media-contribution Quellenangabe wraps (added separately) -
 * not meant to render as their own standalone <dt>/<dd>, same as
 * original_publisher.
 *
 * Idempotent: does nothing if original_author already exists.
 */
class AddSourceCitationPersonInstitutionRolesUpdate implements UpgradeWizardInterface
{
    private const STANDALONE_WRAP = "key.wrap = <dt>|</dt>\r\nvalue.required = 1\r\nvalue.wrap = <dd>|</dd>";

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getNewRows(): array
    {
        $roles = [
            // [index_name, label, mods:name/@type, roleTerm]
            ['original_author', 'Quellenangabe: AutorIn', 'personal', 'aut'],
            ['original_author_1', 'Quellenangabe: 2. AutorIn', 'personal', 'aut'],
            ['original_translator', 'Quellenangabe: ÜbersetzerIn', 'personal', 'trl'],
            ['original_translator_1', 'Quellenangabe: 2. ÜbersetzerIn', 'personal', 'trl'],
            ['original_institution_author', 'Quellenangabe: AutorIn (Institution)', 'corporate', 'aut'],
            ['original_institution_author_1', 'Quellenangabe: 2. AutorIn (Institution)', 'corporate', 'aut'],
            ['original_institution_publisher', 'Quellenangabe: HerausgeberIn (Institution)', 'corporate', 'edt'],
            ['original_institution_publisher_1', 'Quellenangabe: 2. HerausgeberIn (Institution)', 'corporate', 'edt'],
        ];

        $rows = [];
        $sorting = 33160;
        foreach ($roles as [$indexName, $label, $nameType, $roleTerm]) {
            $position = substr($indexName, -2) === '_1' ? 2 : 1;
            $rows[] = [
                'pid' => 1,
                'sorting' => $sorting,
                'index_name' => $indexName,
                'label' => $label,
                'format' => 1,
                'format_type' => 'MODS',
                'xpath' => '(.//mods:relatedItem[@type="host"]/mods:name[@type="' . $nameType . '"]'
                    . '[mods:role/mods:roleTerm="' . $roleTerm . '"])[' . $position . ']/mods:displayForm',
                'wrap' => self::STANDALONE_WRAP,
            ];
            $sorting += 8;
        }

        return $rows;
    }

    public function getIdentifier(): string
    {
        return 'dpfAddSourceCitationPersonInstitutionRoles';
    }

    public function getTitle(): string
    {
        return 'Add host-scoped AutorIn/ÜbersetzerIn/Institution role fields to tx_dpf_metadata';
    }

    public function getDescription(): string
    {
        return 'Adds original_author(_1), original_translator(_1), original_institution_author(_1) and '
            . 'original_institution_publisher(_1) - person/institution roles that existed in the MODS but '
            . 'had no tx_dpf_metadata row at all, needed by #2047\'s Preprint and Beitrag/Interview '
            . 'Quellenangabe rows.';
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
            ['deleted' => 0, 'index_name' => 'original_author']
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
