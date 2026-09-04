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
 * Fixes #2047 "Dokumenttyp" (ubl-26-5006, UW only): the Publikationstyp pill
 * (tx_dpf_metadata uid 8, index_name "type") shows the top-level METS
 * structMap @TYPE (e.g. "article" -> Zeitschriftenartikel, #2048), but a
 * finer sub-classification exists separately on `mods:genre` (e.g.
 * "reviewArticle" for ubl-26-5006) and was never surfaced at all. User
 * decision: show it in the same pill, colon-separated after Publikationstyp
 * (e.g. "Zeitschriftenartikel: reviewArticle").
 *
 * No translation table exists for genre values yet (unlike labels.type for
 * @TYPE, #2048) - the corpus's full genre vocabulary isn't known. Shown
 * as-is for now; a translated-label pass is a natural follow-up once more
 * genre values are seen live, same as labels.type was built up incrementally.
 *
 * Adds a hidden "document_type" row (mods:genre) and appends it to uid 8's
 * wrap, gated on presence (value.append.if.isTrue.field) so plain
 * Publikationstyp-only documents render unchanged. Idempotent: does nothing
 * if a document_type row already exists.
 */
class AddDocumentTypeToPublikationstypPillUpdate implements UpgradeWizardInterface
{
    private const PILL_UID = 8;

    private const OLD_WRAP = "key.wrap = <dt class=\"doctype\">|</dt>\n"
        . "value.required = 1\n"
        . "value.wrap = <dd class=\"doctype\">|</dd>";

    private const NEW_WRAP = "key.wrap = <dt class=\"doctype\">|</dt>\n"
        . "value.required = 1\n"
        . "value.append = TEXT\n"
        . "value.append.value = {field:document_type}\n"
        . "value.append.insertData = 1\n"
        . "value.append.if.isTrue.field = document_type\n"
        . "value.append.noTrimWrap = |: ||\n"
        . "value.wrap3 = <dd class=\"doctype\">|</dd>";

    public function getNewRow(): array
    {
        return [
            'pid' => 1,
            'hidden' => 1,
            'sorting' => 384,
            'index_name' => 'document_type',
            'label' => 'Dokumenttyp',
            'format' => 1,
            'format_type' => 'MODS',
            'xpath' => './mods:genre',
            'wrap' => "key.wrap = <dt>|</dt>\nvalue.required = 1\nvalue.wrap = <dd>|</dd>",
        ];
    }

    public function getIdentifier(): string
    {
        return 'dpfAddDocumentTypeToPublikationstypPill';
    }

    public function getTitle(): string
    {
        return 'Append mods:genre (Dokumenttyp) to the Publikationstyp pill';
    }

    public function getDescription(): string
    {
        return 'Adds a hidden "document_type" row for mods:genre and appends it, colon-separated, to '
            . 'the Publikationstyp pill (uid 8), so ubl-26-5006\'s "reviewArticle"-style sub-classification '
            . 'renders (#2047) - it never had any config row at all.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * connection->count() proved unreliable for a brand-new index_name value
     * within the same test-server process during this fix's development
     * (returned 0 immediately after a committed, MySQL-CLI-visible insert,
     * repeatably, while a raw SELECT COUNT(*) on the same connection saw it
     * correctly) - root cause not pinned down. executeQuery() with a literal
     * SELECT COUNT(*) is what every prior-session wizard in this file
     * already uses for existence checks (see HideDuplicateMetadataRowsUpdate,
     * RestoreHostSeriesPlaceholderRowsUpdate) and is what's used here too.
     */
    private function documentTypeRowExists(\TYPO3\CMS\Core\Database\Connection $connection): bool
    {
        $count = (int) $connection->executeQuery(
            "SELECT COUNT(*) FROM tx_dpf_metadata WHERE deleted = 0 AND index_name = 'document_type'"
        )->fetchColumn();

        return $count > 0;
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        if (!$this->documentTypeRowExists($connection)) {
            return true;
        }

        return $this->computePillFix($connection) !== [];
    }

    /**
     * @return array column/value pairs to update on uid 8, or [] if it's
     *   already fixed. tx_dpf_metadata.wrap stores CRLF line endings, not
     *   LF - normalize both sides before comparing.
     */
    private function computePillFix(\TYPO3\CMS\Core\Database\Connection $connection): array
    {
        $pillRow = $connection->executeQuery(
            'SELECT wrap FROM tx_dpf_metadata WHERE deleted = 0 AND uid = ?',
            [self::PILL_UID]
        )->fetch();

        if (!$pillRow) {
            return [];
        }

        $currentWrap = str_replace("\r\n", "\n", (string) $pillRow['wrap']);
        if (trim($currentWrap) !== self::OLD_WRAP) {
            return [];
        }

        return ['wrap' => self::NEW_WRAP];
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        if (!$this->documentTypeRowExists($connection)) {
            $connection->insert('tx_dpf_metadata', $this->getNewRow());
        }

        $fix = $this->computePillFix($connection);
        if ($fix !== []) {
            $connection->update('tx_dpf_metadata', $fix, ['uid' => self::PILL_UID]);
        }

        return true;
    }
}
