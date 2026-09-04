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
 * Fixes #2046's Vorgänger/Nachfolger double-rendering, confirmed live on
 * qucosa-12691/12516/12743: the "succeeding"/"preceding" composite
 * tx_dpf_metadata rows (uid 238/243, label "Nachfolger"/"Vorgänger") were
 * hidden=0, so they render a link inline in metadataHtml - AND
 * getSequenceItems() merges the same relation into $parentItems, rendered a
 * second time in Show.html's separate parentlink block below metadataHtml.
 * Neither render path was ever marked "embedded" against the other (that
 * mechanism only tracks host/series, see resolveEmbeddableParentItems()).
 *
 * User decision: keep the parentItems block (renders below, and its link
 * had the cHash bug fixed separately in buildRelatedItemUrl()) as the one
 * and only location - hide the metadataHtml composite rows instead of
 * teaching filterEmbeddedRelations() a third relation type, since the
 * parentItems block already has everything (title + link) the composite
 * row had.
 *
 * uid 237 ("Following version", the English l18n overlay of uid 238) needs
 * no separate row - it's pulled in via getRecordOverlay() keyed off uid 238,
 * so hiding 238 alone removes both language variants of "succeeding".
 *
 * Idempotent: does nothing once uid 238/243 are already hidden.
 */
class HideDuplicateSequenceRowsUpdate implements UpgradeWizardInterface
{
    private const AFFECTED_UIDS = [238, 243];

    public function getIdentifier(): string
    {
        return 'dpfHideDuplicateSequenceRows';
    }

    public function getTitle(): string
    {
        return 'Hide duplicate Nachfolger/Vorgänger composite rows';
    }

    public function getDescription(): string
    {
        return 'Hides tx_dpf_metadata uid 238 ("Nachfolger") and 243 ("Vorgänger") so Vorgänger/'
            . 'Nachfolger render only once - via the parentItems block - instead of twice, per #2046.';
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
            'SELECT uid, hidden FROM tx_dpf_metadata WHERE deleted = 0 AND uid IN (?, ?)',
            self::AFFECTED_UIDS
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
            if ((int) $row['hidden'] === 0) {
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
            if ((int) $row['hidden'] === 0) {
                $connection->update('tx_dpf_metadata', ['hidden' => 1], ['uid' => $row['uid']]);
            }
        }

        return true;
    }
}
