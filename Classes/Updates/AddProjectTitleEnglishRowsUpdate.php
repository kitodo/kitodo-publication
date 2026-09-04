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
 * Fixes #2047's "Titel des Projekts (Englisch)" row (ubl-26-5006).
 *
 * Confirmed live against the real METS: each `slub:project` node carries
 * both `@name` (German, already extracted by project_1..project_10, uid
 * 430/431/432/447/448/479-483) and `@nameEn` (English, never extracted at
 * all - no row existed for it):
 *
 *   <slub:project name="Titel des Projekts" nameEn="Titel des Projekts (Englisch)" .../>
 *
 * Fix: add project_1_en..project_10_en rows for `@nameEn`, rendered as their
 * own standalone row directly under the German project_N row.
 *
 * First attempt tried splicing the English title onto project_N's own wrap
 * via `value.append` (single merged "Titel / Titel (Englisch)" row, per the
 * ticket's "ggf. gleich danach, mit Schrägstrich getrennt" - optional
 * phrasing). Caught live on ubl-26-5006 before commit: TYPO3 stdWrap runs
 * `wrap` BEFORE `append` (append needs `wrap3` to land after it, the
 * convention every other multi-part row here already uses, e.g.
 * WRAP_IN_MEDIA/original_in_book's `value.wrap3`) - project_N's simple wrap
 * used `value.wrap`, not `value.wrap3`, and appending after that broke the
 * German value's own display entirely, not just the ordering. Rather than
 * touch project_N's already-working wrap, this ships the two-row version:
 * ticket's core ask (English title now visible at all) is met either way,
 * and "ggf." marks the merge as optional. Revisit only if Leipzig asks for
 * the single-line merge specifically.
 *
 * Idempotent: does nothing if project_1_en already exists.
 */
class AddProjectTitleEnglishRowsUpdate implements UpgradeWizardInterface
{
    private const SLOTS = 10;

    private function englishRowXpath(int $n): string
    {
        return './mods:extension[slub:info/slub:project][' . $n . ']/slub:info/slub:project/@nameEn';
    }

    /**
     * project_N's own sorting value (live on test), so project_N_en can be
     * placed at +1 - immediately after it, matching the ticket's "gleich
     * danach" (right after) placement request.
     *
     * @var array<int, int>
     */
    private const PROJECT_SORTING = [
        1 => 111360, 2 => 111616, 3 => 111872, 4 => 112128, 5 => 112384,
        6 => 112512, 7 => 112576, 8 => 112608, 9 => 112624, 10 => 112632,
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getNewRows(): array
    {
        $rows = [];
        for ($n = 1; $n <= self::SLOTS; $n++) {
            $rows[] = [
                'pid' => 1,
                'sorting' => self::PROJECT_SORTING[$n] + 1,
                'index_name' => 'project_' . $n . '_en',
                'label' => $n . '. Projekt (Englisch)',
                'format' => 1,
                'format_type' => 'MODS',
                'xpath' => $this->englishRowXpath($n),
                'wrap' => "key.wrap = <dt>|</dt>\r\nvalue.required = 1\r\nvalue.wrap = <dd>|</dd>",
            ];
        }
        return $rows;
    }

    public function getIdentifier(): string
    {
        return 'dpfAddProjectTitleEnglishRows';
    }

    public function getTitle(): string
    {
        return 'Add English project-title rows (project_1_en..project_10_en)';
    }

    public function getDescription(): string
    {
        return 'Adds project_1_en..project_10_en (mods:project/@nameEn) as their own standalone rows, '
            . 'fixing #2047\'s missing "Titel des Projekts (Englisch)".';
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
            ['deleted' => 0, 'index_name' => 'project_1_en']
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
