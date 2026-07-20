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
 * Fixes tx_dpf_domain_model_metadatagroup uid 172 ("Institutionsangaben -
 * Sonstige"), whose `mapping` column held the role-exclusion predicates
 * that belong in `mapping_for_reading` instead:
 *
 *   mapping             = /mods:mods/mods:name[@type="corporate"][not(mods:role/mods:roleTerm="dgg")][not(mods:role/mods:roleTerm="prv")][not(mods:role/mods:roleTerm="fnd")]
 *   mapping_for_reading = (empty)
 *
 * `mapping` feeds ParserGenerator::customXPath() when writing new elements;
 * `mapping_for_reading` is only used by DocumentMapper::readDocumentData()
 * to disambiguate existing elements while reading (see sibling group 225,
 * which has the same exclusion predicates correctly placed in
 * mapping_for_reading). Because the first predicate on the write path
 * starts with "@", customXPath() takes its whole-raw-xpath shortcut and
 * carries the not() predicates into the fragment it builds for a new
 * element; XMLFragmentGenerator cannot materialize not(), so the fragment
 * never matches and item(0) on the empty result is null - "Call to a
 * member function item() on null" on any save that fills an "Institution
 * (Sonstige)" field for the first time (#2043).
 *
 * Swaps the two columns to match the working group 225 pattern.
 */
class FixInstitutionGroupMappingUpdate implements UpgradeWizardInterface
{
    private const BROKEN_MAPPING = '/mods:mods/mods:name[@type="corporate"][not(mods:role/mods:roleTerm="dgg")][not(mods:role/mods:roleTerm="prv")][not(mods:role/mods:roleTerm="fnd")]';
    private const FIXED_MAPPING = '/mods:mods/mods:name[@type="corporate"]';

    public function getIdentifier(): string
    {
        return 'dpfFixInstitutionGroupMapping';
    }

    public function getTitle(): string
    {
        return 'Fix Institutionsangaben-Sonstige group mapping/mapping_for_reading swap';
    }

    public function getDescription(): string
    {
        return 'Moves the role-exclusion predicates out of tx_dpf_domain_model_metadatagroup.mapping '
            . '(the write path, used by ParserGenerator) into mapping_for_reading (the read/match path, '
            . 'used by DocumentMapper) for the "Institutionsangaben - Sonstige" group, fixing a crash on '
            . 'save whenever that group is filled in for the first time.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    protected function findBrokenUids(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_domain_model_metadatagroup');

        $rows = $connection->executeQuery(
            'SELECT uid FROM tx_dpf_domain_model_metadatagroup'
            . ' WHERE deleted = 0 AND mapping = ? AND mapping_for_reading = ?',
            [self::BROKEN_MAPPING, '']
        )->fetchAll();

        return array_column($rows, 'uid');
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_domain_model_metadatagroup');

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_domain_model_metadatagroup'])) {
            return false;
        }

        return !empty($this->findBrokenUids());
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_domain_model_metadatagroup');

        foreach ($this->findBrokenUids() as $uid) {
            $connection->update(
                'tx_dpf_domain_model_metadatagroup',
                [
                    'mapping' => self::FIXED_MAPPING,
                    'mapping_for_reading' => self::BROKEN_MAPPING,
                ],
                ['uid' => $uid]
            );
        }

        return true;
    }
}
