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
 * Fixes #2040 item 4 ("Die Autor:innen / Hrsg. ... Gerne über dem Titel"):
 * the personal-editor group (tx_dpf_metadata uid 96, index_name=publisher,
 * label "HerausgeberIn", splices publisher1..10) and the institutional-editor
 * row (uid 43, index_name=corporation_editor, label "Herausgeber (Institution)")
 * both render far below the title (after Klassifikation/DDC) because their
 * `sorting` values (89088 / 102656) sit way past title0's (17408).
 *
 * The personal-author group (uid 395, index_name=authors, sorting 9728)
 * already renders above the title today - no work needed there. This wizard
 * only moves the two editor rows into the same gap, so all three
 * mods:mods/mods:name aut/edt groups (personal authors, personal editors,
 * institutional editors) sit together right after Publikationstyp and before
 * Titel. Verified against real corpus documents that no top-level
 * (non-relatedItem) mods:name ever carries an "aut" role for a corporate
 * name - every hit is either role="prv" at top level or scoped inside a
 * relatedItem - so no corporation_author field is added; ticket's
 * "relatedItems nicht berücksichtigen" instruction makes that data
 * out of scope anyway.
 *
 * Also adds class="editor" hooks to both rows' dt/dd wrap, mirroring the
 * class="author" hooks the "authors" row (uid 395) already ships with, so
 * slub_web_qucosa's CSS can style the editor groups the same way (name
 * prominent, role de-emphasized/on hover - #2040's remaining "nebeneinander"
 * / "Namen prominenter als Rolle" asks) without a text-based selector. The
 * personal-editor <ul> also gets class="editor-list": qsa.scss has a
 * ul:not([class]) li::before rule that draws a circular bullet on any
 * classless list, and its position/border/size properties bled through
 * (property-level cascade, not rule-level) even after the CSS's own
 * li+li::before comma separator "won" the content property - broke the
 * layout live, only a class on the <ul> itself opts out cleanly.
 *
 * Side-by-side layout and name-vs-role prominence are CSS, not data - see
 * landingpage.scss in slub_web_qucosa.
 */
class MoveEditorGroupsAboveTitleUpdate implements UpgradeWizardInterface
{
    private const TITLE_INDEX_NAME = 'title0';

    private const TARGET_SORTING = [
        'publisher'          => 12800,
        'corporation_editor' => 13056,
    ];

    /** [index_name => [old dt/dd wrap needle => new one with class="editor"]] */
    private const CLASS_HOOK_REPLACEMENTS = [
        'publisher' => [
            'key.wrap = <dt>|</dt>' => 'key.wrap = <dt class="editor">|</dt>',
            // Two source needles: pristine original, and the mid-state a test
            // run of this wizard already wrote live before editor-list was
            // added - both must land on the same final string.
            'value.wrap3 = <dd><ul>|</ul></dd>' => 'value.wrap3 = <dd class="editor"><ul class="editor-list">|</ul></dd>',
            'value.wrap3 = <dd class="editor"><ul>|</ul></dd>' =>
                'value.wrap3 = <dd class="editor"><ul class="editor-list">|</ul></dd>',
        ],
        'corporation_editor' => [
            'key.wrap = <dt>|</dt>' => 'key.wrap = <dt class="editor">|</dt>',
            'value.wrap = <dd>|</dd>' => 'value.wrap = <dd class="editor">|</dd>',
        ],
    ];

    public function getIdentifier(): string
    {
        return 'dpfMoveEditorGroupsAboveTitle';
    }

    public function getTitle(): string
    {
        return 'Move personal/institutional editor groups above the title (#2040 item 4)';
    }

    public function getDescription(): string
    {
        return 'Re-sorts the "HerausgeberIn" (uid 96) and "Herausgeber (Institution)" (uid 43) '
            . 'tx_dpf_metadata rows so they render right after the author group and before the '
            . 'title, instead of far down the list after Klassifikation. Also adds class="editor" '
            . 'hooks to their dt/dd wrap for CSS styling.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * @param array $row of [uid, index_name, sorting, wrap]
     * @param int $titleSorting current sorting value of the title0 row
     * @return array column/value pairs to update, or [] if already in place
     */
    public function computeFix(array $row, int $titleSorting): array
    {
        $updates = [];

        $target = self::TARGET_SORTING[$row['index_name']] ?? null;
        if ($target !== null) {
            // Idempotent by construction: once sorting is below the title's,
            // this is a no-op on every re-run - a one-way threshold, nothing
            // to toggle back.
            if ((int) $row['sorting'] >= $titleSorting) {
                $updates['sorting'] = $target;
            }
        }

        $replacements = self::CLASS_HOOK_REPLACEMENTS[$row['index_name']] ?? null;
        if ($replacements !== null) {
            $wrap = strtr((string) ($row['wrap'] ?? ''), $replacements);
            if ($wrap !== $row['wrap']) {
                $updates['wrap'] = $wrap;
            }
        }

        return $updates;
    }

    private function findTitleSorting(): int
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        $sorting = $connection->executeQuery(
            'SELECT sorting FROM tx_dpf_metadata WHERE deleted = 0 AND l18n_parent = 0 AND index_name = ?',
            [self::TITLE_INDEX_NAME]
        )->fetchOne();

        return (int) $sorting;
    }

    protected function findAffectedRows(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        return $connection->executeQuery(
            'SELECT uid, index_name, sorting, wrap FROM tx_dpf_metadata'
            . ' WHERE deleted = 0 AND l18n_parent = 0 AND index_name IN (?, ?)',
            array_keys(self::TARGET_SORTING)
        )->fetchAll();
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        $titleSorting = $this->findTitleSorting();
        foreach ($this->findAffectedRows() as $row) {
            if (!empty($this->computeFix($row, $titleSorting))) {
                return true;
            }
        }

        return false;
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        $titleSorting = $this->findTitleSorting();
        foreach ($this->findAffectedRows() as $row) {
            $fix = $this->computeFix($row, $titleSorting);
            if (!empty($fix)) {
                $connection->update('tx_dpf_metadata', $fix, ['uid' => $row['uid']]);
            }
        }

        return true;
    }
}
