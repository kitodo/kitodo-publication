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
 * Fixes the position scope of the three "Quellenangabe: Herausgeber" xpath
 * rules (original_publisher, original_publisher_1, original_publisher_2) in
 * tx_dpf_metadata (#2041).
 *
 * A source document can carry more than one mods:relatedItem[@type="host"]
 * (verified against qucosa-15470: corporate editors sit in one relatedItem,
 * the personal editor in a second one). The old xpath
 * `./mods:relatedItem[@type="host"]/mods:name[...][N]/mods:displayForm`
 * applies `[N]` *per matched relatedItem*, not across the merged node-set —
 * so editors at the same position in different relatedItems collide into
 * the same field (both a corporate and a personal editor land in
 * original_publisher), while a relatedItem with fewer editors leaves later
 * positions in other relatedItems unmatched.
 *
 * Fix: wrap the node-set in parens before indexing, so `[N]` applies to all
 * host relatedItems' editors in document order as one sequence.
 */
class FixHostEditorPositionScopeUpdate implements UpgradeWizardInterface
{
    private const XPATH_FIXES = [
        'original_publisher' => [
            'old' => './mods:relatedItem[@type="host"]/mods:name[mods:role/mods:roleTerm="edt"][1]/mods:displayForm',
            'new' => '(.//mods:relatedItem[@type="host"]/mods:name[mods:role/mods:roleTerm="edt"])[1]/mods:displayForm',
        ],
        'original_publisher_1' => [
            'old' => './mods:relatedItem[@type="host"]/mods:name[mods:role/mods:roleTerm="edt"][2]/mods:displayForm',
            'new' => '(.//mods:relatedItem[@type="host"]/mods:name[mods:role/mods:roleTerm="edt"])[2]/mods:displayForm',
        ],
        'original_publisher_2' => [
            'old' => './mods:relatedItem[@type="host"]/mods:name[mods:role/mods:roleTerm="edt"][3]/mods:displayForm',
            'new' => '(.//mods:relatedItem[@type="host"]/mods:name[mods:role/mods:roleTerm="edt"])[3]/mods:displayForm',
        ],
    ];

    public function getIdentifier(): string
    {
        return 'dpfFixHostEditorPositionScope';
    }

    public function getTitle(): string
    {
        return 'Fix per-relatedItem position scope in original_publisher(_1/_2) xpath rules';
    }

    public function getDescription(): string
    {
        return 'A document can have multiple mods:relatedItem[@type="host"] elements (e.g. one for '
            . 'corporate editors, one for personal editors). The old xpath applied the [N] position '
            . 'predicate per relatedItem instead of across all of them, causing same-position editors '
            . 'from different relatedItems to collide into one field and later editors to disappear. '
            . 'Rewrites original_publisher/_1/_2 to index the merged node-set instead.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * @param array $row of [index_name, xpath]
     * @return array<string, string> changed fields only
     */
    public function computeFix(array $row): array
    {
        $fix = self::XPATH_FIXES[$row['index_name']] ?? null;
        if ($fix !== null && $row['xpath'] === $fix['old']) {
            return ['xpath' => $fix['new']];
        }

        return [];
    }

    protected function findRowsNeedingFix(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        $rows = $connection->executeQuery(
            'SELECT uid, index_name, xpath FROM tx_dpf_metadata WHERE deleted = 0 AND index_name IN (?, ?, ?)',
            array_keys(self::XPATH_FIXES)
        )->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $fix = $this->computeFix($row);
            if (!empty($fix)) {
                $result[$row['uid']] = $fix;
            }
        }

        return $result;
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        return !empty($this->findRowsNeedingFix());
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        foreach ($this->findRowsNeedingFix() as $uid => $fix) {
            $connection->update('tx_dpf_metadata', $fix, ['uid' => $uid]);
        }

        return true;
    }
}
