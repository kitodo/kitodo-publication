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
 * Fixes #2047's "Titel / Zusammenfassung / Schlagwörter" row (ubl-26-5006):
 * every language is its own hardcoded tx_dpf_metadata row (see
 * AddFrenchTranslatedTitleRowsUpdate for the same pattern), and Georgian
 * (geo), Macedonian (mkd), Swedish (swe) and Catalan (cat) never got one.
 * Confirmed against the live metsdisseminator output which languages the
 * fixture actually carries: geo/mkd/swe only appear on mods:classification
 * (Schlagwörter), cat only on mods:abstract - it has no titleInfo in any of
 * these 4 languages, so no title row is added here.
 *
 * This is an unbounded-language ceiling shared with every other translated-*
 * row (a new language always needs its own new row) - flagged as a
 * follow-up, not fixed here.
 *
 * Idempotent: does nothing if classification_geo already exists.
 */
class AddMissingLanguageRowsUpdate implements UpgradeWizardInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getNewRows(): array
    {
        $rows = [];
        $sorting = 81192;
        foreach (['geo' => 'Georgisch', 'mkd' => 'Mazedonisch', 'swe' => 'Schwedisch'] as $lang => $langLabel) {
            $rows[] = [
                'pid' => 1,
                'sorting' => $sorting,
                'index_name' => 'classification_' . $lang,
                'label' => 'Freie Schlagwörter (' . $langLabel . ')',
                'format' => 1,
                'format_type' => 'MODS',
                'xpath' => './mods:classification[@authority="z"][@lang="' . $lang . '"]',
                'wrap' => "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.wrap = <dd>|</dd>",
            ];
            $sorting += 64;
        }

        $rows[] = [
            'pid' => 1,
            'sorting' => 46464,
            'index_name' => 'abstract_cat',
            'label' => 'Abstract (Katalanisch)',
            'format' => 1,
            'format_type' => 'MODS',
            'xpath' => './mods:abstract[@type="summary"][@lang="cat"]',
            'wrap' => "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.wrap = <dd>|</dd>",
        ];

        return $rows;
    }

    public function getIdentifier(): string
    {
        return 'dpfAddMissingLanguageRows';
    }

    public function getTitle(): string
    {
        return 'Add tx_dpf_metadata rows for Georgian/Macedonian/Swedish keywords and Catalan abstract';
    }

    public function getDescription(): string
    {
        return 'Adds classification_geo / classification_mkd / classification_swe / abstract_cat rows, '
            . 'mirroring the existing per-language pattern, so these languages render on the landing '
            . 'page (#2047) - they never had a config row at all.';
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
            ['deleted' => 0, 'index_name' => 'classification_geo']
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
