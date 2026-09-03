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
 * Fixes #2041's Konferenzband editor row: today the "Konferenzband"
 * Quellenangabe wrap (tx_dpf_metadata uid 307,
 * index_name=original_in_proceeding0000000) repeats "Herausgeber: " on its
 * own line for each of up to 3 editors (original_publisher/_1/_2), e.g.:
 *
 *   Herausgeber: Sapienza – Università di Roma
 *   Herausgeber: Universität Leipzig, Institut für Informatik
 *   Herausgeber: Berti, Monica
 *
 * The ticket wants one line, label shown once, values joined with "; ",
 * and the label renamed to avoid a gendered noun:
 *
 *   Herausgegeben von: Sapienza – Università di Roma; Universität Leipzig,
 *   Institut für Informatik; Berti, Monica
 *
 * The wrap is a flat chain of TypoScript TEXT blocks
 * (value.append.append{...}, value.append.append.append{...}, ...), one per
 * field, each gated by its own fieldRequired. Since original_publisher/_1/_2
 * are populated left-to-right with no gaps (the xpath that fills them is
 * position-indexed - see FixHostEditorPositionScopeUpdate), _1 can never be
 * present without original_publisher also being present, so a fixed "; "
 * prefix on each of the 2nd/3rd blocks is always correct; a trailing block
 * gated on the same field as the first (original_publisher) emits the
 * group's closing <br /> exactly once regardless of how many of the 3 slots
 * are actually filled - avoiding a dangling separator or a missing line
 * break when fewer than 3 editors exist.
 *
 * Every other block in the chain (subtitle, Erscheinungsort, Verlag, ...)
 * is untouched; the wizard rebuilds the append-depth chain from scratch
 * (parsed block-by-block, not string-replaced in place) after swapping the
 * 3 editor blocks for 4, since every block after the insertion point shifts
 * one ".append" level deeper - same renumbering risk flagged when the 3rd
 * editor slot was added to this row, avoided here by regenerating instead
 * of hand-editing the depth strings.
 */
class MergeKonferenzbandEditorsUpdate implements UpgradeWizardInterface
{
    private const INDEX_NAME = 'original_in_proceeding0000000';

    private const NEW_LABEL = 'Herausgegeben von';

    private const EDITOR_FIELDS = ['original_publisher', 'original_publisher_1', 'original_publisher_2'];

    public function getIdentifier(): string
    {
        return 'dpfMergeKonferenzbandEditors';
    }

    public function getTitle(): string
    {
        return 'Merge Konferenzband editors into one "Herausgegeben von: A; B; C" line (#2041)';
    }

    public function getDescription(): string
    {
        return 'Rewrites the Konferenzband Quellenangabe wrap (tx_dpf_metadata uid 307) so its up-to-3 '
            . 'editors render on one line, semicolon-separated, under a single non-gendered label '
            . 'instead of one "Herausgeber: X" line per editor.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * @param array $row of [index_name, wrap]
     * @return array column/value pairs to update, or [] if not applicable / already done
     */
    public function computeFix(array $row): array
    {
        if ($row['index_name'] !== self::INDEX_NAME) {
            return [];
        }

        $wrap = (string) $row['wrap'];

        if (strpos($wrap, self::NEW_LABEL) !== false) {
            return [];
        }

        $fixed = $this->rebuildWrap($wrap);

        if ($fixed === null || $fixed === $wrap) {
            return [];
        }

        return ['wrap' => $fixed];
    }

    /**
     * Splits the wrap into its preamble (everything before the first
     * "value.append" block) and its ordered list of block bodies, replaces
     * the 3 editor blocks with the merged-line shape, then regenerates the
     * whole append-depth chain.
     */
    private function rebuildWrap(string $wrap): ?string
    {
        $preambleEnd = strpos($wrap, 'value.append = TEXT');
        if ($preambleEnd === false) {
            return null;
        }
        $preamble = substr($wrap, 0, $preambleEnd);
        $chain = substr($wrap, $preambleEnd);

        if (!preg_match_all('/\{\r\n(.*?)\r\n\}/s', $chain, $matches)) {
            return null;
        }
        $blocks = $matches[1];

        $editorIndexes = $this->findEditorBlockIndexes($blocks);
        if ($editorIndexes === null) {
            return null;
        }

        return $preamble . $this->renderChain($this->replaceEditorBlocks($blocks, $editorIndexes));
    }

    /**
     * @param string[] $blocks
     * @return array<string, int>|null field => block index, or null if the
     *   row doesn't have the expected 3 editor blocks (already changed, or a
     *   shape this wizard doesn't recognize).
     */
    private function findEditorBlockIndexes(array $blocks): ?array
    {
        // Anchored to end-of-string so e.g. "original_publisher" doesn't
        // false-match a block whose field is actually "original_publisher_1".
        $indexes = [];
        foreach ($blocks as $i => $body) {
            foreach (self::EDITOR_FIELDS as $field) {
                if (preg_match('/fieldRequired = ' . preg_quote($field, '/') . '$/', $body)) {
                    $indexes[$field] = $i;
                }
            }
        }

        return count($indexes) === count(self::EDITOR_FIELDS) ? $indexes : null;
    }

    /**
     * @param string[] $blocks
     * @param array<string, int> $editorIndexes
     * @return string[]
     */
    private function replaceEditorBlocks(array $blocks, array $editorIndexes): array
    {
        $skip = [$editorIndexes['original_publisher_1'], $editorIndexes['original_publisher_2']];

        $newBlocks = [];
        foreach ($blocks as $i => $body) {
            if ($i === $editorIndexes['original_publisher']) {
                array_push($newBlocks, ...$this->editorGroupBlocks());
                continue;
            }
            if (in_array($i, $skip, true)) {
                continue; // folded into editorGroupBlocks() above
            }
            $newBlocks[] = $body;
        }

        return $newBlocks;
    }

    /** @param string[] $blocks */
    private function renderChain(array $blocks): string
    {
        $result = '';
        foreach ($blocks as $depth => $body) {
            $path = 'value' . str_repeat('.append', $depth + 1);
            $result .= "{$path} = TEXT\r\n{$path} {\r\n{$body}\r\n}\r\n";
        }

        return $result;
    }

    /** @return string[] the 4 replacement blocks for the 3 original editor blocks */
    private function editorGroupBlocks(): array
    {
        return [
            "\tvalue = {field:original_publisher}\r\n"
                . "\tvalue.insertData = 1\r\n"
                . "\tvalue.noTrimWrap = |" . self::NEW_LABEL . ": | \r\n"
                . "\tvalue.noTrimWrap.fieldRequired = original_publisher",
            "\tvalue = {field:original_publisher_1}\r\n"
                . "\tvalue.insertData = 1\r\n"
                . "\tvalue.noTrimWrap = |; | \r\n"
                . "\tvalue.noTrimWrap.fieldRequired = original_publisher_1",
            "\tvalue = {field:original_publisher_2}\r\n"
                . "\tvalue.insertData = 1\r\n"
                . "\tvalue.noTrimWrap = |; | \r\n"
                . "\tvalue.noTrimWrap.fieldRequired = original_publisher_2",
            "\tvalue = <br />\r\n"
                . "\tvalue.fieldRequired = original_publisher",
        ];
    }

    protected function findAffectedRows(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        return $connection->executeQuery(
            'SELECT uid, index_name, wrap FROM tx_dpf_metadata WHERE deleted = 0 AND index_name = ?',
            [self::INDEX_NAME]
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
