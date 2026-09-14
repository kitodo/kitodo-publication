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
 * Fixes #2047's "Titel des Projekts (Englisch)" row (`ubl-26-5006`). An
 * earlier session added `project_N_en` (uid 604-613) as standalone rows,
 * then hid them again after Leipzig flagged the extra visible "1. Projekt
 * (Englisch)" line as wrong - their Soll wants the English title merged
 * inline with the German one, separated by " / ", inside the existing
 * "Förder- / Projektangaben" composite (uid 496), not shown separately.
 *
 * `project_N`'s own TEXT object (one of 10 near-identical slots in uid
 * 496's append chain, at increasing depth per slot) currently does:
 *   value = {field:project_N}
 *   value.insertData = 1
 * A prior attempt at this exact merge added a sibling `value.append` next
 * to `value.noTrimWrap` - broken, because stdWrap applies `append` *after*
 * `wrap`/`noTrimWrap` in TYPO3's fixed processing order, so the appended
 * English title landed after project_N's own `<br />`, not merged into the
 * same line. Fixed here by building the combined string *before*
 * `noTrimWrap` sees it, via `value.cObject = COA` (two TEXT sub-objects:
 * the German title, then the English title conditionally suffixed with
 * " / " via its own `required`) - `value.noTrimWrap` (unchanged, still
 * gated on `project_N` alone) then wraps the already-merged result exactly
 * as before.
 *
 * Idempotent: does nothing once uid 496's wrap already contains
 * `project_1_en` (proof this wizard already ran).
 */
class MergeProjectEnglishTitleIntoFundingUpdate implements UpgradeWizardInterface
{
    private const COMPOSITE_UID = 496;
    private const SLOT_COUNT = 10;

    public function getIdentifier(): string
    {
        return 'dpfMergeProjectEnglishTitleIntoFunding';
    }

    public function getTitle(): string
    {
        return 'Merge "Titel des Projekts (Englisch)" inline into Förder-/Projektangaben';
    }

    public function getDescription(): string
    {
        return 'Merges project_N_en into project_N\'s own line (" / " separator) inside the '
            . '"Förder- / Projektangaben" composite wrap, per #2047 (ubl-26-5006), instead of a '
            . 'separate standalone row.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        $wrap = (string) $connection->executeQuery(
            'SELECT wrap FROM tx_dpf_metadata WHERE uid = ?',
            [self::COMPOSITE_UID]
        )->fetchColumn(0);

        return $wrap !== '' && strpos($wrap, 'project_1_en') === false;
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        $wrap = (string) $connection->executeQuery(
            'SELECT wrap FROM tx_dpf_metadata WHERE uid = ?',
            [self::COMPOSITE_UID]
        )->fetchColumn(0);

        if ($wrap === '' || strpos($wrap, 'project_1_en') !== false) {
            return true;
        }

        $connection->update(
            'tx_dpf_metadata',
            ['wrap' => $this->mergeEnglishTitles($wrap)],
            ['uid' => self::COMPOSITE_UID]
        );

        return true;
    }

    /**
     * Replaces each slot's `value = {field:project_N}` / `value.insertData
     * = 1` line pair with a `value.cObject = COA` block that concatenates
     * project_N and (conditionally) project_N_en before noTrimWrap ever
     * sees the string. Anchored on the exact `{field:project_N}` marker -
     * unique per slot regardless of the surrounding indentation, which
     * varies (tabs vs spaces) between slots in the real row.
     *
     * KNOWN GAP, not resolved this session: the separator between German
     * and English title renders as a bare "/" with no surrounding space
     * ("Titel/Titel (Englisch)"), not Leipzig's "Titel / Titel (Englisch)".
     * `value.wrap = / |` is used here because it's the confirmed-working
     * variant; a `value.noTrimWrap = &nbsp;/ |` attempt (meant to preserve
     * the leading space per TSref's documented wrap-vs-noTrimWrap trim
     * behavior, matching this same wrap's own `project_acronym` sibling's
     * "|&nbsp(|)" idiom) instead dropped the whole prefix at render time -
     * root cause not diagnosed (2 sub-attempts at the separator spacing
     * specifically; stopped per the session's retry-limit directive). The
     * substantive merge itself (single line, no separate row, English
     * title present) is confirmed correct; only this cosmetic spacing is
     * open.
     */
    public function mergeEnglishTitles(string $wrap): string
    {
        for ($slot = 1; $slot <= self::SLOT_COUNT; $slot++) {
            // Matches "value = {field:project_N}" followed by an
            // "value.insertData = 1" line, tolerant of the leading
            // whitespace between the two lines actually varying (tabs on
            // some slots, spaces on others - confirmed live on uid 496).
            $pattern = '/value = \{field:project_' . $slot . '\}\r\n[ \t]*value\.insertData = 1\r\n/';
            if (!preg_match($pattern, $wrap)) {
                // Structure doesn't match what was confirmed live for this
                // slot; skip rather than guess.
                continue;
            }

            $replacement = "value.cObject = COA\r\n"
                . "value.cObject {\r\n"
                . "\t10 = TEXT\r\n"
                . "\t10.value = {field:project_{$slot}}\r\n"
                . "\t10.insertData = 1\r\n"
                . "\t20 = TEXT\r\n"
                . "\t20 {\r\n"
                . "\t\tvalue = {field:project_{$slot}_en}\r\n"
                . "\t\tvalue.insertData = 1\r\n"
                . "\t\tvalue.wrap = / |\r\n"
                . "\t\tvalue.required = 1\r\n"
                . "\t}\r\n"
                . "}\r\n";

            $wrap = (string) preg_replace($pattern, str_replace('$', '\$', $replacement), $wrap, 1);
        }

        return $wrap;
    }
}
