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
 * Fixes #2047 (UBL-26-5006/UBL-26-5007): AddProjectTitleEnglishRowsUpdate
 * added project_N_en rows (uid 604-613) with their own visible <dt>/<dd>
 * wrap, so "N. Projekt (Englisch)" now renders as its own standalone
 * metadata row right above "Förder- / Projektangaben" - not what Leipzig
 * asked for. Their Soll is the English title merged inline into the
 * existing "Förder- / Projektangaben" row (project_funding, uid 20/496),
 * next to the German title.
 *
 * That inline merge is a separate, larger change to project_funding's
 * per-slot TypoScript (10 near-identical COA blocks) and is deferred.
 * This wizard only removes the wrong standalone row Leipzig flagged as a
 * regression: it hides project_N_en (findRenderableFields() filters
 * hidden=1, so it drops out of the render loop) while its xpath keeps
 * extracting normally (findExtractionRules() does not filter on hidden),
 * so {field:project_N_en} stays available once the inline merge is built.
 */
class HideProjectEnglishTitleRowsUpdate implements UpgradeWizardInterface
{
    private const AFFECTED_INDEX_NAMES = [
        'project_1_en', 'project_2_en', 'project_3_en', 'project_4_en', 'project_5_en',
        'project_6_en', 'project_7_en', 'project_8_en', 'project_9_en', 'project_10_en',
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
        return 'dpfHideProjectEnglishTitleRows';
    }

    public function getTitle(): string
    {
        return 'Hide the standalone "Projekt (Englisch)" rows added by AddProjectTitleEnglishRowsUpdate';
    }

    public function getDescription(): string
    {
        return 'Sets hidden=1 on project_1_en..project_10_en so the English project title stops '
            . 'rendering as its own metadata row (#2047 regression); extraction is unaffected, so the '
            . 'field stays available once it is merged inline into "Förder- / Projektangaben" instead.';
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
