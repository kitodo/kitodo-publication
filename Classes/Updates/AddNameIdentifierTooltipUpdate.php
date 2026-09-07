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
 * Fixes #2047 (UBL-26-5007): affiliation/ORCID/ResearcherID/GND-ID/Scopus
 * Author ID should be visible "on second look" for a person's name, not
 * inline. LandingPageAssembler::buildNameIdentifierTooltips() now computes
 * a per-position tooltip string as authorIds1/publisherIds1 pseudo-fields;
 * this wizard adds title="{field:authorIds1}" / title="{field:publisherIds1}"
 * to the first author/editor slot's wrap so it actually renders.
 *
 * Institution roles (corporation_author/corporation_editor) don't need a DB
 * change - they render through parseFieldValue()'s plain array_shift loop,
 * which the same commit wires directly to the tooltip data for every
 * position, not just the first.
 *
 * Scoped to position 1 only (the common case: most documents have one
 * author). Positions 2-3 are a straightforward follow-up in the same shape
 * once this is confirmed on a live document.
 */
class AddNameIdentifierTooltipUpdate implements UpgradeWizardInterface
{
    private const AUTHOR_INDEX_NAME = 'authors';
    private const AUTHOR_OLD = 'value.override = <dd class="author">{field:author1} <span class="affiliation" style="display:none;">{field:affiliation1}</span></dd>';
    private const AUTHOR_NEW = 'value.override = <dd class="author" title="{field:authorIds1}">{field:author1} <span class="affiliation" style="display:none;">{field:affiliation1}</span></dd>';

    private const PUBLISHER_INDEX_NAME = 'publisher';
    private const PUBLISHER_OLD = 'value.dataWrap = <li>{field:publisher1}</li>';
    private const PUBLISHER_NEW = 'value.dataWrap = <li title="{field:publisherIds1}">{field:publisher1}</li>';

    /**
     * @param array $row of [uid, index_name, wrap]
     * @return array
     */
    public function computeFix(array $row): array
    {
        if ($row['index_name'] === self::AUTHOR_INDEX_NAME && strpos((string) $row['wrap'], self::AUTHOR_OLD) !== false) {
            return ['wrap' => str_replace(self::AUTHOR_OLD, self::AUTHOR_NEW, (string) $row['wrap'])];
        }
        if ($row['index_name'] === self::PUBLISHER_INDEX_NAME && strpos((string) $row['wrap'], self::PUBLISHER_OLD) !== false) {
            return ['wrap' => str_replace(self::PUBLISHER_OLD, self::PUBLISHER_NEW, (string) $row['wrap'])];
        }

        return [];
    }

    public function getIdentifier(): string
    {
        return 'dpfAddNameIdentifierTooltip';
    }

    public function getTitle(): string
    {
        return 'Add ORCID/GND/ResearcherID/Scopus tooltip to the first AutorIn/HerausgeberIn slot';
    }

    public function getDescription(): string
    {
        return 'Adds title="{field:authorIds1}" / title="{field:publisherIds1}" to the first person '
            . 'slot of the AutorIn and HerausgeberIn rows, so affiliation/ORCID/ResearcherID/GND-ID/'
            . 'Scopus Author ID show as a native mouseover tooltip (#2047, UBL-26-5007) instead of not '
            . 'at all.';
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
            'SELECT uid, index_name, wrap FROM tx_dpf_metadata WHERE deleted = 0 AND index_name IN (?, ?)',
            [self::AUTHOR_INDEX_NAME, self::PUBLISHER_INDEX_NAME]
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
