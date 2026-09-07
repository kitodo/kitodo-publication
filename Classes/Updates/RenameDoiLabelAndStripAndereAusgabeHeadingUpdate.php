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
 * Fixes the two easy parts of #2041 (ubl-26-5023) "Andere Ausgabe": DOI und
 * Link. The full ask - move DOI/Link up next to ISBN/ISSN, drop the "Andere
 * Ausgabe" group entirely - needs restructuring the otherVersion00 row's
 * 5-slot COA TypoScript (uid 225) and is deferred as a separate, larger
 * change (too much surface for one surgical fix, given no controller tests
 * to catch a COA syntax regression).
 *
 * This wizard only does the two safe, independent renames Leipzig also
 * asked for:
 *  - "doi" (uid 253, the Qucosa document's own DOI) -> "DOI (Qucosa)", so
 *    it reads distinctly from the "Andere Ausgabe" DOI values.
 *  - "otherVersion00" (uid 225) label -> '' (empty), which drops the
 *    "Andere Ausgabe" heading Leipzig asked to have removed, while leaving
 *    its DOI/Link/note values rendering exactly as before.
 */
class RenameDoiLabelAndStripAndereAusgabeHeadingUpdate implements UpgradeWizardInterface
{
    private const DOI_INDEX_NAME = 'doi';
    private const DOI_OLD_LABEL = 'DOI';
    private const DOI_NEW_LABEL = 'DOI (Qucosa)';

    private const HEADING_INDEX_NAME = 'otherVersion00';
    private const HEADING_OLD_LABEL = 'Andere Ausgabe';
    // A real empty string falls back to the raw index_name in
    // LandingPageAssembler::getMetadataHtml() ('label' => $resArray['label'] ?: $resArray['index_name']
    // - PHP's ?: treats '' as falsy). A single space is non-empty (survives the fallback) but renders
    // as no visible heading text, which is what "drop the heading" actually needs.
    private const HEADING_NEW_LABEL = ' ';

    /**
     * @param array $row of [uid, index_name, label]
     * @return array
     */
    public function computeFix(array $row): array
    {
        if ($row['index_name'] === self::DOI_INDEX_NAME && $row['label'] === self::DOI_OLD_LABEL) {
            return ['label' => self::DOI_NEW_LABEL];
        }
        if (
            $row['index_name'] === self::HEADING_INDEX_NAME
            && ($row['label'] === self::HEADING_OLD_LABEL || $row['label'] === '')
        ) {
            return ['label' => self::HEADING_NEW_LABEL];
        }

        return [];
    }

    public function getIdentifier(): string
    {
        return 'dpfRenameDoiLabelAndStripAndereAusgabeHeading';
    }

    public function getTitle(): string
    {
        return 'Rename "DOI" to "DOI (Qucosa)" and drop the "Andere Ausgabe" heading label';
    }

    public function getDescription(): string
    {
        return 'Renames the Qucosa document\'s own DOI row to "DOI (Qucosa)" and blanks the '
            . '"Andere Ausgabe" group heading (#2041, ubl-26-5023) - the full restructure (moving '
            . 'DOI/Link up next to ISBN/ISSN) is a separate, larger follow-up.';
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
            'SELECT uid, index_name, label FROM tx_dpf_metadata WHERE deleted = 0 AND index_name IN (?, ?)',
            [self::DOI_INDEX_NAME, self::HEADING_INDEX_NAME]
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
