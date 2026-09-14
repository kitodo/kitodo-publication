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
 * Fixes #2047 (Erik Sommer, 2026-09-14 session): the affiliation hover box
 * shipped for personal AutorIn (uid 394's "Authors" wrap already carries
 * affiliation1/2/3 spans for all 3 positions) was never wired for personal
 * HerausgeberIn ("publisher" index_name, uid 96) - that wrap's <li> items
 * have no affiliation span at any position, only the title="" tooltip added
 * by AddNameIdentifierTooltipUpdate/AddPositionalTooltipsToAuthorPublisherUpdate.
 *
 * The existing Affiliation1..15 field family (uid 417/416/415/...) is scoped
 * to mods:role/mods:roleTerm="aut" only - confirmed via live DB read, this is
 * NOT a document-order-global field, so it cannot be reused for editors
 * as-is. This wizard adds a parallel 3-position AffiliationEditor1..3 family
 * (matching the existing 3-slot cap on publisherIds/HerausgeberIn tooltips -
 * positions 4-10 have names but never got a tooltip either, a pre-existing
 * limitation out of this fix's scope) using the exact xpath shape of uid 94
 * ("1. Herausgeber", the live, working HerausgeberIn name field) with
 * /mods:affiliation appended, then splices the new fields into the 3 <li>
 * wraps as a <span class="affiliation" style="display:none;"> matching
 * uid 394's pattern exactly.
 */
class AddAffiliationToEditorSlotsUpdate implements UpgradeWizardInterface
{
    private const PUBLISHER_INDEX_NAME = 'publisher';

    private const AFFILIATION_EDITOR_XPATH = './mods:name[@type="personal"][.//mods:roleTerm[@type="code" and @authority="marcrelator"]="edt"][%d]/mods:affiliation';

    private const PUBLISHER_OLD = [
        1 => 'value.dataWrap = <li title="{field:publisherIds1}">{field:publisher1}</li>',
        2 => 'value.wrap = <li title="{field:publisherIds2}">|</li>',
        3 => 'value.wrap = <li title="{field:publisherIds3}">|</li>',
    ];

    private const PUBLISHER_NEW = [
        1 => 'value.dataWrap = <li title="{field:publisherIds1}">{field:publisher1} <span class="affiliation" style="display:none;">{field:affiliationEditor1}</span></li>',
        2 => 'value.wrap = <li title="{field:publisherIds2}">|<span class="affiliation" style="display:none;">{field:affiliationEditor2}</span></li>',
        3 => 'value.wrap = <li title="{field:publisherIds3}">|<span class="affiliation" style="display:none;">{field:affiliationEditor3}</span></li>',
    ];

    public function getIdentifier(): string
    {
        return 'dpfAddAffiliationToEditorSlots';
    }

    public function getTitle(): string
    {
        return 'Add affiliation hover box to the personal HerausgeberIn slots';
    }

    public function getDescription(): string
    {
        return 'Adds a new AffiliationEditor1/2/3 field family (mods:role/roleTerm="edt", positions 1-3) '
            . 'and splices <span class="affiliation"> into the HerausgeberIn <li> wrap for each position, '
            . 'matching the AutorIn affiliation hover box (#2047).';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    private function getConnection(): \TYPO3\CMS\Core\Database\Connection
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');
    }

    private function affiliationEditorFieldsExist(): bool
    {
        $connection = $this->getConnection();
        $count = $connection->executeQuery(
            "SELECT COUNT(*) FROM tx_dpf_metadata WHERE index_name IN "
                . "('affiliationEditor1', 'affiliationEditor2', 'affiliationEditor3')"
        )->fetchColumn();

        return ((int) $count) >= 3;
    }

    private function findAffectedPublisherRow(): ?array
    {
        $connection = $this->getConnection();
        $row = $connection->executeQuery(
            'SELECT uid, wrap FROM tx_dpf_metadata WHERE deleted = 0 AND index_name = ?',
            [self::PUBLISHER_INDEX_NAME]
        )->fetch();

        return $row ?: null;
    }

    private function computePublisherWrapFix(string $wrap): ?string
    {
        $new = $wrap;
        $changed = false;
        foreach (self::PUBLISHER_OLD as $position => $old) {
            if (strpos($new, $old) !== false) {
                $new = str_replace($old, self::PUBLISHER_NEW[$position], $new);
                $changed = true;
            }
        }

        return $changed ? $new : null;
    }

    public function updateNecessary(): bool
    {
        $connection = $this->getConnection();
        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        if (!$this->affiliationEditorFieldsExist()) {
            return true;
        }

        $row = $this->findAffectedPublisherRow();
        if ($row !== null && $this->computePublisherWrapFix((string) $row['wrap']) !== null) {
            return true;
        }

        return false;
    }

    public function executeUpdate(): bool
    {
        $connection = $this->getConnection();

        if (!$this->affiliationEditorFieldsExist()) {
            $template = $connection->executeQuery(
                'SELECT * FROM tx_dpf_metadata WHERE uid = 417'
            )->fetch();

            if ($template !== false) {
                unset($template['uid']);
                foreach ([1, 2, 3] as $position) {
                    $data = $template;
                    $data['label'] = 'AffiliationEditor' . $position;
                    $data['index_name'] = 'affiliationEditor' . $position;
                    $data['xpath'] = sprintf(self::AFFILIATION_EDITOR_XPATH, $position);
                    $data['sorting'] = (int) $template['sorting'] + 100000 + $position;
                    $connection->insert('tx_dpf_metadata', $data);
                }
            }
        }

        $row = $this->findAffectedPublisherRow();
        if ($row !== null) {
            $newWrap = $this->computePublisherWrapFix((string) $row['wrap']);
            if ($newWrap !== null) {
                $connection->update('tx_dpf_metadata', ['wrap' => $newWrap], ['uid' => $row['uid']]);
            }
        }

        return true;
    }
}
