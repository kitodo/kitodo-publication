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
 * Base for wizards that change tx_dpf_metadata rows. A wizard only decides
 * what to change: computeChanges() gets all live rows and returns
 * ['updates' => [uid => [column => value]], 'inserts' => [row, ...]]. It
 * returns nothing once the rows are in the wanted state, so a second run
 * changes nothing. Rows are matched by index_name, never by uid, because
 * the uids differ between systems.
 */
abstract class AbstractMetadataRowUpdate implements UpgradeWizardInterface
{
    private const COLUMNS = 'uid, pid, index_name, label, wrap, sorting, hidden, sys_language_uid, l18n_parent, xpath';

    /**
     * @param array<int, array<string, mixed>> $rows all rows with deleted = 0
     * @return array{updates: array<int, array<string, mixed>>, inserts: array<int, array<string, mixed>>}
     */
    abstract public function computeChanges(array $rows): array;

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        if (!$this->tableExists()) {
            return false;
        }

        $changes = $this->computeChanges($this->findRows());

        return !empty($changes['updates']) || !empty($changes['inserts']);
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        $changes = $this->computeChanges($this->findRows());

        foreach ($changes['updates'] as $uid => $fields) {
            $connection->update('tx_dpf_metadata', $fields, ['uid' => $uid]);
        }
        foreach ($changes['inserts'] as $row) {
            $connection->insert('tx_dpf_metadata', $row);
        }

        return true;
    }

    /** @return array<int, array<string, mixed>> */
    protected function findRows(): array
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata')
            ->executeQuery('SELECT ' . self::COLUMNS . ' FROM tx_dpf_metadata WHERE deleted = 0')
            ->fetchAll();
    }

    private function tableExists(): bool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata')
            ->getSchemaManager()
            ->tablesExist(['tx_dpf_metadata']);
    }

    /**
     * Main-language rows with the given index_name (not overlay rows).
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>> uid => row
     */
    protected static function mainRowsByIndexName(array $rows, string $indexName): array
    {
        $found = [];
        foreach ($rows as $row) {
            if (
                $row['index_name'] === $indexName
                && (int)$row['l18n_parent'] === 0
                && (int)$row['sys_language_uid'] === 0
            ) {
                $found[(int)$row['uid']] = $row;
            }
        }

        return $found;
    }

    /** @param array<int, array<string, mixed>> $rows */
    protected static function hasIndexName(array $rows, string $indexName): bool
    {
        return self::mainRowsByIndexName($rows, $indexName) !== [];
    }

    /**
     * Splits a Quellenangabe-style wrap, a flat chain of TypoScript TEXT
     * blocks (value.append{...}, value.append.append{...}, ...), into its
     * preamble and its ordered block bodies.
     *
     * @return array{0: string, 1: string[]}|null null if the wrap has no such chain
     */
    protected static function parseWrapChain(string $wrap): ?array
    {
        $start = strpos($wrap, 'value.append = TEXT');
        if ($start === false) {
            return null;
        }
        if (!preg_match_all('/\{\r?\n(.*?)\r?\n\}/s', substr($wrap, $start), $matches)) {
            return null;
        }

        return [substr($wrap, 0, $start), $matches[1]];
    }

    /**
     * Writes the block chain back. Every block after an insertion or removal
     * moves to another ".append" depth, so the depth strings are regenerated
     * instead of being edited in place.
     *
     * @param string[] $blocks
     */
    protected static function renderWrapChain(string $preamble, array $blocks): string
    {
        $result = $preamble;
        foreach (array_values($blocks) as $depth => $body) {
            $path = 'value' . str_repeat('.append', $depth + 1);
            $result .= "{$path} = TEXT\r\n{$path} {\r\n{$body}\r\n}\r\n";
        }

        return $result;
    }

    /** The {field:x} a block prints, or null (e.g. for a plain <br /> block). */
    protected static function blockField(string $body): ?string
    {
        return preg_match('/^\s*value = \{field:(\w+)\}/m', $body, $m) ? $m[1] : null;
    }

    /** A text block that prints one field with a label and a line break. */
    protected static function labelledFieldBlock(string $field, string $label): string
    {
        return "\tvalue = {field:{$field}}\r\n"
            . "\tvalue.insertData = 1\r\n"
            . "\tvalue.noTrimWrap = |{$label}: | <br />\r\n"
            . "\tvalue.noTrimWrap.fieldRequired = {$field}";
    }
}
