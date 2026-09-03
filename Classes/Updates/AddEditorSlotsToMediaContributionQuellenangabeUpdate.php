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
 * Adds 2nd/3rd editor slots to the Beitrag/Interview ("in_media") Quellenangabe
 * wrap in tx_dpf_metadata (#2041), same family as
 * AddThirdEditorSlotToConferenceQuellenangabeUpdate: this doctype's wrap only
 * ever chained in {field:original_publisher}, silently dropping every
 * document's 2nd+ editor. original_publisher_1/_2 already extract correctly
 * (xpath fixed by FixHostEditorPositionScopeUpdate) - this wizard only
 * extends the wrap that splices them in, matching the 3-slot cap the
 * Konferenzband/Sammelband/Nachschlagewerk rows already use.
 *
 * Unlike the Konferenzband wrap, this doctype's original_publisher block
 * carries a trailing "wrap = |<br />" line before its closing brace - the
 * insertion point search accounts for that extra line.
 */
class AddEditorSlotsToMediaContributionQuellenangabeUpdate implements UpgradeWizardInterface
{
    private const TARGET_INDEX_NAME = 'original_in_media';
    private const AFTER_FIELD = 'original_publisher';
    private const NEW_FIELDS = ['original_publisher_1', 'original_publisher_2'];

    public function getIdentifier(): string
    {
        return 'dpfAddEditorSlotsToMediaContributionQuellenangabe';
    }

    public function getTitle(): string
    {
        return 'Add 2nd/3rd editor slots to the Beitrag/Interview Quellenangabe wrap';
    }

    public function getDescription(): string
    {
        return 'The Beitrag/Interview ("in_media") Quellenangabe wrap only chains in '
            . 'original_publisher, so a document with 2+ editors silently drops all but '
            . 'the first. Splices in original_publisher_1 and original_publisher_2 right '
            . 'after it, matching the 3-slot cap Konferenzband/Sammelband/Nachschlagewerk '
            . 'already use.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * @param string $wrap the row's current wrap TypoScript
     * @return string|null the new wrap, or null if $wrap doesn't need (or
     *   already has) the fix
     */
    public function computeFix(string $wrap): ?string
    {
        // Already fixed, or a shape we don't recognize - never touch it.
        if (strpos($wrap, self::NEW_FIELDS[0]) !== false) {
            return null;
        }

        $lines = preg_split('/\r\n|\r|\n/', $wrap);
        $insertion = $this->locateInsertionPoint($lines);
        if ($insertion === null) {
            return null;
        }
        [$closeBraceIdx, $newPrefix] = $insertion;

        $newBlockLines = [];
        foreach (self::NEW_FIELDS as $i => $field) {
            $prefix = $newPrefix . str_repeat('.append', $i);
            $newBlockLines = array_merge($newBlockLines, [
                "{$prefix} = TEXT",
                "{$prefix} {",
                "\tvalue = {field:" . $field . '}',
                "\tvalue.insertData = 1",
                "\tvalue.noTrimWrap = |Herausgeber: | <br />",
                "\tvalue.noTrimWrap.fieldRequired = " . $field,
                "}",
            ]);
        }

        $before = array_slice($lines, 0, $closeBraceIdx + 1);
        $after = array_slice($lines, $closeBraceIdx + 1);
        foreach ($after as &$line) {
            $line = preg_replace_callback('/value(?:\.append)+/', function (array $m): string {
                return $m[0] . '.append.append';
            }, $line);
        }
        unset($line);

        return implode("\r\n", array_merge($before, $newBlockLines, $after));
    }

    /**
     * Finds the AFTER_FIELD block's closing brace and the ".append" chain
     * prefix the new blocks need, one level deeper than AFTER_FIELD's own.
     *
     * Block shape (this doctype only): header(-6) open-brace(-5) value(-4)
     * insertData(-3) noTrimWrap(-2) fieldRequired(-1) wrap-line(0) close-brace(+1).
     *
     * @param string[] $lines
     * @return array{0: int, 1: string}|null [close-brace line index, new prefix]
     */
    private function locateInsertionPoint(array $lines): ?array
    {
        $fieldRequiredLine = 'value.noTrimWrap.fieldRequired = ' . self::AFTER_FIELD;
        $fieldReqIdx = array_search($fieldRequiredLine, array_map('trim', $lines), true);
        if ($fieldReqIdx === false
            || !isset($lines[$fieldReqIdx + 1], $lines[$fieldReqIdx + 2])
            || trim($lines[$fieldReqIdx + 1]) !== 'wrap = |<br />'
            || trim($lines[$fieldReqIdx + 2]) !== '}'
        ) {
            return null;
        }

        $closeBraceIdx = $fieldReqIdx + 2;
        $headerIdx = $closeBraceIdx - 7;
        if (!isset($lines[$headerIdx])
            || !preg_match('/^(value(?:\.append)+) = TEXT$/', $lines[$headerIdx], $hm)
        ) {
            return null;
        }

        return [$closeBraceIdx, $hm[1] . '.append'];
    }

    protected function findRowsNeedingFix(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        $rows = $connection->executeQuery(
            'SELECT uid, wrap FROM tx_dpf_metadata WHERE deleted = 0 AND index_name = ?',
            [self::TARGET_INDEX_NAME]
        )->fetchAll();

        $result = [];
        foreach ($rows as $row) {
            $fix = $this->computeFix($row['wrap']);
            if ($fix !== null) {
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

        foreach ($this->findRowsNeedingFix() as $uid => $newWrap) {
            $connection->update('tx_dpf_metadata', ['wrap' => $newWrap], ['uid' => $uid]);
        }

        return true;
    }
}
