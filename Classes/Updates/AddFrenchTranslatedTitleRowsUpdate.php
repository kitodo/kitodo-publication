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
 * Fixes #2039 red item "Übersetzter Titel (FRZ)" (Zeitschrift, qucosa-12799):
 * every other translated-title language (RUS, HRV, POL, GER, EN, SWA, HSB,
 * PER) has an extraction row pair (title1_<lang>/subtitle1_<lang>) plus a
 * composite display row (title_<lang>), but French never got one. Live MODS
 * for qucosa-12799 confirms the data exists
 * (mods:titleInfo[@lang="fre"][@type="translated"]/mods:title =
 * "Journal d'onomastique") and that lang="fre" is the correct code already
 * used successfully elsewhere (classification_fre, abstract_fre) - this is a
 * missing config row, not an xpath bug.
 *
 * Adds the three rows mirroring the EN/GER pattern (see uid 340/346/351).
 * Idempotent: does nothing if a translated_title1_fre row already exists.
 */
class AddFrenchTranslatedTitleRowsUpdate implements UpgradeWizardInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getNewRows(): array
    {
        return [
        [
            'pid' => 1,
            'sorting' => 20650,
            'index_name' => 'translated_title1_fre',
            'label' => 'Übersetzter Haupttitel (FRZ)',
            'format' => 1,
            'format_type' => 'MODS',
            'xpath' => 'concat(./mods:titleInfo[@lang="fre"][@type="translated"]/mods:nonSort,'
                . '" ",./mods:titleInfo[@lang="fre"][@type="translated"]/mods:title)',
            'wrap' => "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.wrap = <dd>|</dd>",
        ],
        [
            'pid' => 1,
            'sorting' => 20670,
            'index_name' => 'translated_subtitle1_fre',
            'label' => 'Übersetzter Untertitel (FRZ)',
            'format' => 1,
            'format_type' => 'MODS',
            'xpath' => 'concat(./mods:titleInfo[@lang="fre"][@type="translated"]/mods:nonSort,'
                . '" ",./mods:titleInfo[@lang="fre"][@type="translated"]/mods:subTitle)',
            'wrap' => "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.wrap = <dd>|</dd>",
        ],
        [
            'pid' => 1,
            'sorting' => 20690,
            'index_name' => 'translated_title_fre',
            'label' => 'Übersetzter Titel (FRZ)',
            'format' => 0,
            'format_type' => '',
            'xpath' => '',
            'wrap' => "key.wrap = <dt>|</dt>\n"
                . "value.fieldRequired = translated_title1_fre\n"
                . "value.dataWrap = {field:translated_title1_fre}\n"
                . "value.append = TEXT\n"
                . "value.append {\n"
                . "\tvalue = {field:translated_subtitle1_fre}\n"
                . "\tvalue.insertData = 1\n"
                . "\tvalue.noTrimWrap = | : ||\n"
                . "\tvalue.noTrimWrap.fieldRequired = translated_subtitle1_fre\n"
                . "}\n"
                . "value.wrap = <dd>|</dd>",
        ],
        ];
    }

    public function getIdentifier(): string
    {
        return 'dpfAddFrenchTranslatedTitleRows';
    }

    public function getTitle(): string
    {
        return 'Add tx_dpf_metadata rows for French translated titles';
    }

    public function getDescription(): string
    {
        return 'Adds translated_title1_fre / translated_subtitle1_fre / translated_title_fre rows, '
            . 'mirroring the existing English/German rows, so French translated titles render on the '
            . 'landing page (they never had a config row at all).';
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
            ['deleted' => 0, 'index_name' => 'translated_title1_fre']
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
