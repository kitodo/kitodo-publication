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
 * Completes #2041's "Andere Ausgabe" fix (`ubl-26-5023`): DOI and Link are
 * identifiers of the publication, same as ISBN and ISSN above, so they
 * belong in the identifier group. PromoteOtherVersionIdentifiersUpdate
 * already narrowed "DOI (Qucosa)" and stripped DOI/Link out of the 5-slot
 * "Andere Ausgabe" block. This wizard adds the two new rows that show
 * those values instead.
 *
 * A first attempt tried to reuse two existing rows that looked like dead
 * placeholders (empty xpath, hidden=0). They turned out to be the English
 * l18n overlays of "DOI (Qucosa)" and "PURL" (sys_language_uid=1), which
 * findRenderableFields() never renders on the main German surface. Two
 * genuinely new rows are added here instead, sys_language_uid=0 and
 * l18n_parent=0 like every other main-language row.
 *
 * The xpath is unscoped by position (matches every "andere Ausgabe"
 * relatedItem at once, not just the first five slots the doi_1..5/url_1..5
 * fields were limited to), so parseFieldValue()'s existing per-value loop
 * renders one <dd> per value with no hand-built append chain and no cap on
 * how many can show.
 */
class AddOtherVersionIdentifierRowsUpdate implements UpgradeWizardInterface
{
    private const DOI_WRAP = "key.wrap = <dt>|</dt>\r\nvalue.required = 1\r\nvalue.setContentToCurrent = 1\r\n"
        . "value.innerWrap = https://doi.org/|\r\nvalue.typolink.parameter.current = 1\r\n"
        . "value.typolink.parameter.prepend = TEXT\r\nvalue.typolink.parameter.prepend.value = https://doi.org/\r\n"
        . "value.wrap = <dd>|</dd>";

    private const LINK_WRAP = "key.wrap = <dt>|</dt>\r\nvalue.required = 1\r\nvalue.setContentToCurrent = 1\r\n"
        . "value.typolink.parameter.current = 1\r\nvalue.wrap = <dd>|</dd>";

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getNewRows(): array
    {
        return [
            [
                'pid' => 1,
                'sorting' => 46312,
                'index_name' => 'otherversion_doi',
                'label' => 'DOI',
                'format' => 1,
                'format_type' => 'MODS',
                'xpath' => './mods:relatedItem[@type="otherversion"]/mods:identifier[@type="doi"]',
                'wrap' => self::DOI_WRAP,
            ],
            [
                'pid' => 1,
                'sorting' => 46320,
                'index_name' => 'otherversion_url',
                'label' => 'Link',
                'format' => 1,
                'format_type' => 'MODS',
                'xpath' => './mods:relatedItem[@type="otherversion"]/mods:location/mods:url',
                'wrap' => self::LINK_WRAP,
            ],
        ];
    }

    public function getIdentifier(): string
    {
        return 'dpfAddOtherVersionIdentifierRows';
    }

    public function getTitle(): string
    {
        return 'Add DOI/Link rows for "Andere Ausgabe" to the identifier group (#2041)';
    }

    public function getDescription(): string
    {
        return 'Adds "otherversion_doi"/"otherversion_url" (labelled "DOI"/"Link") next to ISBN/ISSN, so the '
            . '"Andere Ausgabe" DOI and Link values (removed from the old 5-slot block by '
            . 'PromoteOtherVersionIdentifiersUpdate) render again, in the identifier group as the ticket asked.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    private function getConnection()
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_dpf_metadata');
    }

    private function countByIndexName(string $indexName): int
    {
        return (int) $this->getConnection()->executeQuery(
            'SELECT COUNT(*) FROM tx_dpf_metadata WHERE deleted = 0 AND index_name = ?',
            [$indexName]
        )->fetchColumn(0);
    }

    public function updateNecessary(): bool
    {
        $connection = $this->getConnection();

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        return $this->countByIndexName('otherversion_doi') === 0;
    }

    public function executeUpdate(): bool
    {
        $connection = $this->getConnection();

        foreach ($this->getNewRows() as $row) {
            if ($this->countByIndexName($row['index_name']) === 0) {
                $connection->insert('tx_dpf_metadata', $row);
            }
        }

        return true;
    }
}
