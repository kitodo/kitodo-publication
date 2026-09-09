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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Fixes #2047's "Beziehungen" row (`UBL-26-5088`): every relatedItem type
 * shares one sub-field shape (Titel/Bemerkung/URL/URN/DOI/Handle/ISBN/ISSN/
 * ZDB-ID), confirmed live for series/preceding/succeeding - but "Übergeordnete
 * Schriftenreihe" (series_*) and "Überordnung"/"Mehrbändiges Werk"
 * (multivolume_*) only ever extracted Titel(+URN/local), missing Bemerkung/
 * URL/DOI/Handle/ISBN/ISSN/ZDB-ID.
 *
 * Ticket explicitly exempts Vorgänger/Nachfolger (preceding/succeeding -
 * link-only display is correct there) and "Verweis" (references_* rows
 * already cover Titel/Bemerkung/DOI/URL). Only series_* and multivolume_*
 * get new rows here.
 *
 * Idempotent: does nothing if series_note already exists.
 */
class AddRelationDetailFieldsUpdate implements UpgradeWizardInterface
{
    private const STANDALONE_WRAP = "key.wrap = <dt>|</dt>\r\nvalue.required = 1\r\nvalue.wrap = <dd>|</dd>";

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getNewRows(): array
    {
        $definitions = [
            // [index_name, label, relatedItem @type, mods sub-path]
            ['series_note', 'Schriftenreihe: Bemerkung', 'series', 'mods:note'],
            ['series_url', 'Schriftenreihe: URL', 'series', 'mods:location/mods:url'],
            ['series_doi', 'Schriftenreihe: DOI', 'series', 'mods:identifier[@type="doi"]'],
            ['series_handle', 'Schriftenreihe: Handle', 'series', 'mods:identifier[@type="handle"]'],
            ['series_isbn', 'Schriftenreihe: ISBN', 'series', 'mods:identifier[@type="isbn"]'],
            ['series_issn', 'Schriftenreihe: ISSN', 'series', 'mods:identifier[@type="issn"]'],
            ['series_zdb', 'Schriftenreihe: ZDB-ID', 'series', 'mods:identifier[@type="zdb"]'],
            ['multivolume_note', 'Mehrbändiges Werk: Bemerkung', 'host', 'mods:note'],
            ['multivolume_url', 'Mehrbändiges Werk: URL', 'host', 'mods:location/mods:url'],
            ['multivolume_doi', 'Mehrbändiges Werk: DOI', 'host', 'mods:identifier[@type="doi"]'],
            ['multivolume_handle', 'Mehrbändiges Werk: Handle', 'host', 'mods:identifier[@type="handle"]'],
            ['multivolume_isbn', 'Mehrbändiges Werk: ISBN', 'host', 'mods:identifier[@type="isbn"]'],
            ['multivolume_issn', 'Mehrbändiges Werk: ISSN', 'host', 'mods:identifier[@type="issn"]'],
            ['multivolume_zdb', 'Mehrbändiges Werk: ZDB-ID', 'host', 'mods:identifier[@type="zdb"]'],
            ['multivolume_volume', 'Mehrbändiges Werk: Bandzählung', 'host', 'mods:part[@type="volume"]/mods:detail/mods:number'],
        ];

        $rows = [];
        $sorting = 40000;
        foreach ($definitions as [$indexName, $label, $relType, $subPath]) {
            $rows[] = [
                'pid' => 1,
                'sorting' => $sorting,
                'index_name' => $indexName,
                'label' => $label,
                'format' => 1,
                'format_type' => 'MODS',
                'xpath' => './mods:relatedItem[@type="' . $relType . '"]/' . $subPath,
                'wrap' => self::STANDALONE_WRAP,
            ];
            $sorting += 8;
        }

        return $rows;
    }

    public function getIdentifier(): string
    {
        return 'dpfAddRelationDetailFields';
    }

    public function getTitle(): string
    {
        return 'Add Bemerkung/URL/DOI/Handle/ISBN/ISSN/ZDB-ID rows to Schriftenreihe and Mehrbändiges Werk';
    }

    public function getDescription(): string
    {
        return 'Adds the missing "Beziehungen" sub-fields for the series and host (Überordnung) relatedItem '
            . 'types, per #2047 - every relation type is meant to show the full set except Vorgänger/'
            . 'Nachfolger (link-only) and Verweis (already complete).';
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

        return $this->countByIndexName($connection, 'series_note') === 0;
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        foreach ($this->getNewRows() as $row) {
            if ($this->countByIndexName($connection, $row['index_name']) === 0) {
                $connection->insert('tx_dpf_metadata', $row);
            }
        }

        return true;
    }

    /**
     * Counts non-deleted rows for one index_name via a raw query, not
     * Connection::count() - tx_dpf_metadata's TCA declares hidden as its
     * enablecolumns.disabled field, so Connection::count()'s QueryBuilder
     * silently applies a HiddenRestriction on top of the caller's own WHERE.
     * A hidden=1 row (e.g. HideRelationDetailStandaloneRowsUpdate, #2047)
     * then reads back as 0, and this wizard wrongly re-inserts it - found
     * live when re-running this wizard duplicated all 15 rows. Same
     * restriction-bypass MetadataMappingRepository::findExtractionRules()
     * already documents and uses for the same table.
     */
    private function countByIndexName(Connection $connection, string $indexName): int
    {
        return (int) $connection->executeQuery(
            'SELECT COUNT(*) FROM tx_dpf_metadata WHERE deleted = 0 AND index_name = ?',
            [$indexName]
        )->fetchColumn(0);
    }
}
