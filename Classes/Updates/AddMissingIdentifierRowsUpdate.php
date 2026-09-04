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
 * Fixes #2047 "Alle Identifier müssen angezeigt werden" (ubl-26-5018 /
 * UBL-26-5088): confirmed against the live metsdisseminator output (not the
 * raw Fedora datastream - see FixIssnOtherVersionScopeUpdate for the same
 * caveat) that URN/Handle/URI/ISMN/ZDB/Andere all exist on these fixtures
 * but only under `relatedItem[@type="otherversion"]`, never as a
 * document-level identifier - the same scope gap ISBN/ISSN already had.
 * "doi" (uid 253) and "zdb" (uid 273) already have a row but only match the
 * document-level scope, silently dropping otherversion-scoped values (e.g.
 * doi shows one DOI, not three). URN/Handle/URI/ISMN/Andere have no row at
 * all yet. ISI/PMID are added too (ticket also lists them) even though no
 * live fixture carries either value to verify against - same pattern,
 * genuinely untestable live today.
 */
class AddMissingIdentifierRowsUpdate implements UpgradeWizardInterface
{
    private const WIDENED_INDEX_NAMES = ['doi', 'zdb'];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getNewRows(): array
    {
        $labels = [
            'identifier_urn'    => 'URN',
            'identifier_handle' => 'Handle',
            'identifier_uri'    => 'URI',
            'identifier_ismn'   => 'ISMN',
            'identifier_other'  => 'Andere',
            'identifier_isi'    => 'ISI',
            'identifier_pmid'   => 'PMID',
        ];
        $types = [
            'identifier_urn'    => 'urn',
            'identifier_handle' => 'handle',
            'identifier_uri'    => 'uri',
            'identifier_ismn'   => 'ismn',
            'identifier_other'  => 'other',
            'identifier_isi'    => 'isi',
            'identifier_pmid'   => 'pmid',
        ];

        $rows = [];
        $sorting = 46112;
        foreach ($labels as $indexName => $label) {
            $type = $types[$indexName];
            $rows[] = [
                'pid' => 1,
                'sorting' => $sorting,
                'index_name' => $indexName,
                'label' => $label,
                'format' => 1,
                'format_type' => 'MODS',
                'xpath' => $this->widenedXpath($type),
                'wrap' => "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.wrap = <dd>|</dd>",
            ];
            $sorting += 32;
        }
        return $rows;
    }

    private function widenedXpath(string $type): string
    {
        return './mods:identifier[@type="' . $type . '"]'
            . ' | ./mods:relatedItem[@type="otherversion"]/mods:identifier[@type="' . $type . '"]';
    }

    /**
     * Given a tx_dpf_metadata row, returns the column/value pairs it needs
     * updated, or [] if the row is already fine / not affected.
     *
     * @param array $row of [uid, index_name, xpath]
     * @return array
     */
    public function computeFix(array $row): array
    {
        if (!in_array($row['index_name'], self::WIDENED_INDEX_NAMES, true)) {
            return [];
        }
        $oldXpath = './mods:identifier[@type="' . $row['index_name'] . '"]';
        if (trim((string) $row['xpath']) !== $oldXpath) {
            return [];
        }

        return ['xpath' => $this->widenedXpath($row['index_name'])];
    }

    public function getIdentifier(): string
    {
        return 'dpfAddMissingIdentifierRows';
    }

    public function getTitle(): string
    {
        return 'Add missing identifier rows and widen doi/zdb to the otherversion scope';
    }

    public function getDescription(): string
    {
        return 'Adds URN/Handle/URI/ISMN/Andere/ISI/PMID tx_dpf_metadata rows, and widens the '
            . 'existing doi/zdb rows to also match relatedItem[@type="otherversion"], mirroring the '
            . 'ISBN/ISSN fix, so every own-document identifier renders on the landing page (#2047).';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    protected function findWidenableRows(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        return $connection->executeQuery(
            'SELECT uid, index_name, xpath FROM tx_dpf_metadata WHERE deleted = 0 AND index_name IN (?, ?)',
            self::WIDENED_INDEX_NAMES
        )->fetchAll();
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        foreach ($this->findWidenableRows() as $row) {
            if (!empty($this->computeFix($row))) {
                return true;
            }
        }

        $existing = $connection->count(
            'uid',
            'tx_dpf_metadata',
            ['deleted' => 0, 'index_name' => 'identifier_urn']
        );

        return $existing === 0;
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        foreach ($this->findWidenableRows() as $row) {
            $fix = $this->computeFix($row);
            if (!empty($fix)) {
                $connection->update('tx_dpf_metadata', $fix, ['uid' => $row['uid']]);
            }
        }

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
