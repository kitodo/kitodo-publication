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
 * Adds a 3rd editor slot to the Konferenzband ("in_proceeding") Quellenangabe
 * wrap in tx_dpf_metadata (#2041), matching the pattern the Sammelband
 * ("contained_work") and Nachschlagewerk ("in_encyclopedia") rows already
 * use: chain in a {field:original_publisher_2} TEXT block right after
 * {field:original_publisher_1}'s, then bump every later field in the chain
 * one ".append" level deeper.
 *
 * original_publisher/_1/_2 already extract correctly (their xpath was fixed
 * by FixHostEditorPositionScopeUpdate) - this wizard only extends the
 * Konferenzband wrap that splices them in, which previously stopped at 2
 * editors and silently dropped a document's 3rd one.
 */
class AddThirdEditorSlotToConferenceQuellenangabeUpdate implements UpgradeWizardInterface
{
    private const TARGET_INDEX_NAME = 'original_in_proceeding0000000';
    private const AFTER_FIELD = 'original_publisher_1';
    private const NEW_FIELD = 'original_publisher_2';

    public function getIdentifier(): string
    {
        return 'dpfAddThirdEditorSlotToConferenceQuellenangabe';
    }

    public function getTitle(): string
    {
        return 'Add 3rd editor slot to the Konferenzband Quellenangabe wrap';
    }

    public function getDescription(): string
    {
        return 'The Konferenzband ("in_proceeding") Quellenangabe wrap only chains in '
            . 'original_publisher and original_publisher_1, so a document with 3 editors '
            . 'silently drops the 3rd. Splices in original_publisher_2 right after '
            . 'original_publisher_1, matching the Sammelband/Nachschlagewerk rows, which '
            . 'already go to 3 editors.';
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
        if (strpos($wrap, self::NEW_FIELD) !== false) {
            return null;
        }

        $lines = preg_split('/\r\n|\r|\n/', $wrap);
        $insertion = $this->locateInsertionPoint($lines);
        if ($insertion === null) {
            return null;
        }
        [$closeBraceIdx, $newPrefix] = $insertion;

        $newBlockLines = [
            "{$newPrefix} = TEXT",
            "{$newPrefix} {",
            "\tvalue = {field:" . self::NEW_FIELD . '}',
            "\tvalue.insertData = 1",
            "\tvalue.noTrimWrap = |Herausgeber: | <br />",
            "\tvalue.noTrimWrap.fieldRequired = " . self::NEW_FIELD,
            "}",
        ];

        $before = array_slice($lines, 0, $closeBraceIdx + 1);
        $after = array_slice($lines, $closeBraceIdx + 1);
        foreach ($after as &$line) {
            $line = preg_replace_callback('/value(?:\.append)+/', function (array $m): string {
                return $m[0] . '.append';
            }, $line);
        }
        unset($line);

        return implode("\r\n", array_merge($before, $newBlockLines, $after));
    }

    /**
     * Finds the AFTER_FIELD block's closing brace and the ".append" chain
     * prefix the new block needs, one level deeper than AFTER_FIELD's own.
     *
     * Block shape: header(-6) open-brace(-5) value(-4) insertData(-3)
     * noTrimWrap(-2) fieldRequired(-1) close-brace(0).
     *
     * @param string[] $lines
     * @return array{0: int, 1: string}|null [close-brace line index, new prefix]
     */
    private function locateInsertionPoint(array $lines): ?array
    {
        $fieldRequiredLine = 'value.noTrimWrap.fieldRequired = ' . self::AFTER_FIELD;
        $fieldReqIdx = array_search($fieldRequiredLine, array_map('trim', $lines), true);
        if ($fieldReqIdx === false || !isset($lines[$fieldReqIdx + 1])
            || trim($lines[$fieldReqIdx + 1]) !== '}'
        ) {
            return null;
        }

        $closeBraceIdx = $fieldReqIdx + 1;
        $headerIdx = $closeBraceIdx - 6;
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
