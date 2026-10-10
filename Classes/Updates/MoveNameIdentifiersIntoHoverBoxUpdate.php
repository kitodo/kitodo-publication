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
 * Fixes #2051 (UBL-26-5007): a person shows two kinds of hover box. The blue
 * box lists the affiliations. A native title="" tooltip lists the affiliations
 * and identifiers. The AutorIn and HerausgeberIn wraps now drop the title=""
 * and fill the blue box (span.affiliation) with the text of the old tooltip:
 * all affiliations, then the identifiers.
 */
class MoveNameIdentifiersIntoHoverBoxUpdate implements UpgradeWizardInterface
{
    public function getIdentifier(): string
    {
        return 'dpfMoveNameIdentifiersIntoHoverBox';
    }

    public function getTitle(): string
    {
        return 'Show AutorIn/HerausgeberIn identifiers in the blue hover box (#2051)';
    }

    public function getDescription(): string
    {
        return 'Removes the title="" tooltip from the AutorIn and HerausgeberIn wraps. '
            . 'The blue hover box shows its text instead.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    private function getConnection()
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');
    }

    /**
     * @return string|null the converted wrap, or null if it has no name identifier title=""
     */
    public function convertWrap(string $wrap): ?string
    {
        $converted = preg_replace('/ title="\{field:(?:author|publisher)Ids\d+\}"/', '', $wrap, -1, $count);
        if ($count === 0) {
            return null;
        }

        $converted = preg_replace('/\{field:affiliation(\d+)\}/', '{field:authorIds$1}', $converted);
        $converted = preg_replace('/\{field:affiliationEditor(\d+)\}/', '{field:publisherIds$1}', $converted);

        return $converted;
    }

    private function pendingUpdates(): array
    {
        $rows = $this->getConnection()->executeQuery(
            'SELECT uid, wrap FROM tx_dpf_metadata WHERE wrap LIKE ?',
            ['%Ids%']
        )->fetchAll();

        $updates = [];
        foreach ($rows as $row) {
            $newWrap = $this->convertWrap((string) $row['wrap']);
            if ($newWrap !== null) {
                $updates[$row['uid']] = $newWrap;
            }
        }

        return $updates;
    }

    public function updateNecessary(): bool
    {
        if (!$this->getConnection()->getSchemaManager()->tablesExist(['tx_dpf_metadata'])) {
            return false;
        }

        return !empty($this->pendingUpdates());
    }

    public function executeUpdate(): bool
    {
        foreach ($this->pendingUpdates() as $uid => $wrap) {
            $this->getConnection()->update('tx_dpf_metadata', ['wrap' => $wrap], ['uid' => $uid]);
        }

        return true;
    }
}
