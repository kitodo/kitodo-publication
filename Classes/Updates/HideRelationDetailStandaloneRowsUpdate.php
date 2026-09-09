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
 * Fixes #2047 (UBL-26-5088): AddRelationDetailFieldsUpdate added the
 * series_* / multivolume_* sub-fields (Bemerkung/URL/DOI/Handle/ISBN/ISSN/
 * ZDB-ID) as their own standalone rows, so each rendered as a separate
 * "Schriftenreihe: X"/"Mehrbändiges Werk: X" <dt>, scattered away from the
 * "Erschienen in"/"Schriftenreihe" block that already shows the relatedItem's
 * title link - not what the ticket asked for (all sub-fields merged into
 * that same block, as extra <dd> lines).
 *
 * That merge is done in LandingPageAssembler::renderEmbeddedParentItemRow()/
 * relationDetailLines(), which reads these fields directly off $metadata.
 * This wizard only hides the now-redundant standalone rows: hidden=1 makes
 * findRenderableFields() drop them from the render loop, while their xpath
 * keeps extracting normally (findExtractionRules() does not filter on
 * hidden), so the merged block keeps working.
 */
class HideRelationDetailStandaloneRowsUpdate implements UpgradeWizardInterface
{
    private const AFFECTED_INDEX_NAMES = [
        'series_note', 'series_url', 'series_doi', 'series_handle', 'series_isbn', 'series_issn', 'series_zdb',
        'multivolume_note', 'multivolume_url', 'multivolume_doi', 'multivolume_handle',
        'multivolume_isbn', 'multivolume_issn', 'multivolume_zdb', 'multivolume_volume',
    ];

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
        return 'dpfHideRelationDetailStandaloneRows';
    }

    public function getTitle(): string
    {
        return 'Hide the standalone series_*/multivolume_* rows, merge them into the host/series block instead';
    }

    public function getDescription(): string
    {
        return 'Sets hidden=1 on the series_*/multivolume_* rows added by AddRelationDetailFieldsUpdate '
            . '(#2047) so they stop rendering as their own "Schriftenreihe: X"/"Mehrbändiges Werk: X" rows; '
            . 'extraction is unaffected, so LandingPageAssembler can keep reading them into the merged '
            . '"Erschienen in"/"Schriftenreihe" block.';
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
