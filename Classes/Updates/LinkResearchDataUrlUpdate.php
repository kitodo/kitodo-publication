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
 * Fixes #2039 red item "Forschungsdatenverweis" (Buch, qucosa-80365): the
 * label renders but the link doesn't. Extraction is fine (researchData_url1
 * xpath correctly matches qucosa-80365's mods:relatedItem[@type="references"]
 * URL) - the row's wrap just prints the value as plain text
 * (`value.wrap = <dd>|</dd>`), so the URL shows as inert text, never as an
 * `<a href>`.
 *
 * Adds a self-referencing typolink (the URL field is both link text and
 * target), same dataWrap.typolink shape as
 * EmbedHostLinkInQuellenangabeUpdate.php's host-link fix.
 */
class LinkResearchDataUrlUpdate implements UpgradeWizardInterface
{
    private const AFFECTED_INDEX_NAMES = [
        'researchData_url1',
        'researchData_url2',
        'researchData_url3',
        'researchData_url4',
    ];

    private const OLD_VALUE_WRAP = 'value.wrap = <dd>|</dd>';

    public function getIdentifier(): string
    {
        return 'dpfLinkResearchDataUrl';
    }

    public function getTitle(): string
    {
        return 'Render Forschungsdatenverweis URLs as links, not plain text';
    }

    public function getDescription(): string
    {
        return 'Adds a typolink to the researchData_url1-4 tx_dpf_metadata rows so the '
            . 'extracted research-data URL renders as a clickable link instead of inert text.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * @param array $row of [uid, index_name, wrap]
     * @return array
     */
    public function computeFix(array $row): array
    {
        if (!in_array($row['index_name'], self::AFFECTED_INDEX_NAMES, true)) {
            return [];
        }

        $wrap = (string) $row['wrap'];
        $indexName = $row['index_name'];

        if (strpos($wrap, 'value.dataWrap.typolink.parameter') !== false) {
            return [];
        }

        if (strpos($wrap, self::OLD_VALUE_WRAP) === false) {
            return [];
        }

        $newValueWrap = "value.dataWrap = {field:$indexName}\n"
            . "value.dataWrap.typolink.parameter = {field:$indexName}\n"
            . "value.dataWrap.typolink.parameter.fieldRequired = $indexName\n"
            . self::OLD_VALUE_WRAP;

        $wrap = str_replace(self::OLD_VALUE_WRAP, $newValueWrap, $wrap);

        return ['wrap' => $wrap];
    }

    protected function findAffectedRows(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        return $connection->executeQuery(
            'SELECT uid, index_name, wrap FROM tx_dpf_metadata WHERE deleted = 0 AND index_name IN (?, ?, ?, ?)',
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
