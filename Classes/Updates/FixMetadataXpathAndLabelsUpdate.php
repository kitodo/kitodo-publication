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
 * Fixes xpath and label content in tx_dpf_metadata found during UBL landing
 * page QA (#2039, #2042):
 *
 * - 36 rules match `mods:relatedItem[@type="otherVersion"]` (camelCase), but
 *   source MODS always writes lowercase `otherversion` — DOMXPath is
 *   case-sensitive, so these rules silently never matched, even though the
 *   underlying content exists (verified against 22 sample documents, zero
 *   camelCase occurrences). Fixes: edition, publication_date, and 34 other
 *   otherVersion-scoped fields (publisher, doi_1-5, url_1-5, note1-5, etc).
 * - `corporation_other` (role="oth" corporate names) expects a
 *   `mods:displayForm` child, but source MODS never has one there — only
 *   `mods:namePart` (role="edt" names do carry displayForm; role="oth" never
 *   does, confirmed against every role="oth" occurrence in the sample).
 * - Field label renames per #2042: "Sprache des Dokumentes" -> "Sprache",
 *   "Dokumenttyp" -> "Publikationstyp".
 * - Per-type "Quellenangabe" headers (already type-scoped via
 *   `value.if.equals.field = type` in the wrap column) get their specific
 *   label per #2042's table, and their page-range separator switched from
 *   a hyphen to an en dash.
 *
 * Only covers the 3 existing type-conditioned Quellenangabe rows
 * (in_proceeding, contained_work, article). New rows for Nachschlagewerk,
 * Blog, and the generic "Erschienen in" fallback are out of scope here —
 * one has no verified `type` value yet and one has no test fixture at all
 * (per #2042, "Testbeispiel muss noch erstellt werden").
 *
 * MetadataExtractor.php is untouched — this is content-only, same pattern
 * as FixIsbnMetadataCollisionUpdate.
 */
class FixMetadataXpathAndLabelsUpdate implements UpgradeWizardInterface
{
    private const QUELLENANGABE_LABELS = [
        'original_in_proceeding0000000' => 'Konferenzband',
        'original_in_book' => 'Sammelband',
        'original0000000000' => 'Zeitschrift',
    ];

    public function getIdentifier(): string
    {
        return 'dpfFixMetadataXpathAndLabels';
    }

    public function getTitle(): string
    {
        return 'Fix otherVersion/displayForm xpath bugs and rename metadata labels in tx_dpf_metadata';
    }

    public function getDescription(): string
    {
        return 'Corrects a case-sensitivity bug (otherVersion -> otherversion) affecting 36 xpath '
            . 'rules, a wrong-element bug in corporation_other (displayForm -> namePart), renames '
            . 'Dokumenttyp/Sprache des Dokumentes field labels, and renames the type-specific '
            . 'Quellenangabe headers (Konferenzband/Sammelband/Zeitschrift) including their '
            . 'page-range separator (hyphen -> en dash).';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * Compute the field changes a row needs, or [] if it's already correct.
     *
     * @param array $row of [index_name, label, xpath, wrap]
     * @return array<string, string> changed fields only
     */
    public function computeFix(array $row): array
    {
        $updates = [];

        $xpath = $row['xpath'];
        if (strpos($xpath, 'otherVersion') !== false) {
            $xpath = str_replace('otherVersion', 'otherversion', $xpath);
        }
        if ($row['index_name'] === 'corporation_other' && strpos($xpath, 'mods:displayForm') !== false) {
            $xpath = str_replace('mods:displayForm', 'mods:namePart', $xpath);
        }
        if ($xpath !== $row['xpath']) {
            $updates['xpath'] = $xpath;
        }

        if ($row['index_name'] === 'language' && $row['label'] === 'Sprache des Dokumentes') {
            $updates['label'] = 'Sprache';
        }
        if (in_array($row['index_name'], ['type', 'genre'], true) && $row['label'] === 'Dokumenttyp') {
            $updates['label'] = 'Publikationstyp';
        }
        if (isset(self::QUELLENANGABE_LABELS[$row['index_name']]) && $row['label'] === 'Quellenangabe') {
            $updates['label'] = self::QUELLENANGABE_LABELS[$row['index_name']];
        }

        if (isset(self::QUELLENANGABE_LABELS[$row['index_name']]) && strpos($row['wrap'], '|-| <br />') !== false) {
            $updates['wrap'] = str_replace('|-| <br />', '|–| <br />', $row['wrap']);
        }

        return $updates;
    }

    protected function findRowsNeedingFix(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        $rows = $connection->executeQuery(
            'SELECT uid, index_name, label, xpath, wrap FROM tx_dpf_metadata WHERE deleted = 0'
        )->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $fix = $this->computeFix($row);
            if (!empty($fix)) {
                $result[$row['uid']] = $fix;
            }
        }

        return $result;
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        return !empty($this->findRowsNeedingFix());
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        foreach ($this->findRowsNeedingFix() as $uid => $fix) {
            $connection->update('tx_dpf_metadata', $fix, ['uid' => $uid]);
        }

        return true;
    }
}
