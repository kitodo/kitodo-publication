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
 * Fixes #2048: the backend document-edit-form Publikationstyp dropdown
 * (tx_dpf_domain_model_documenttype.display_name) still shows a
 * "(Herausgabe)" suffix on 5 types - leftover from the legacy naming, not
 * present anywhere in the landing-page label maps (plugin.tx_dpf_metadata /
 * tx_dpf_landingpage .labels.type, both already suffix-free). Strips it so
 * the dropdown and the landing page read the same.
 */
class StripHerausgabeSuffixFromDocumentTypeUpdate implements UpgradeWizardInterface
{
    private const AFFECTED_NAMES = ['proceeding', 'editedCollection', 'sourceEdition', 'encyclopedia', 'specialIssue'];

    private const SUFFIX = ' (Herausgabe)';

    /**
     * @param array $row of [uid, name, display_name]
     * @return array
     */
    public function computeFix(array $row): array
    {
        if (!in_array($row['name'], self::AFFECTED_NAMES, true)) {
            return [];
        }
        if (substr($row['display_name'], -strlen(self::SUFFIX)) !== self::SUFFIX) {
            return [];
        }

        return ['display_name' => substr($row['display_name'], 0, -strlen(self::SUFFIX))];
    }

    public function getIdentifier(): string
    {
        return 'dpfStripHerausgabeSuffixFromDocumentType';
    }

    public function getTitle(): string
    {
        return 'Strip the "(Herausgabe)" suffix from 5 document type dropdown labels';
    }

    public function getDescription(): string
    {
        return 'Renames "Konferenzband/Sammelband/Quellenedition/Nachschlagewerk/Sonderheft einer '
            . 'Zeitschrift (Herausgabe)" to drop the "(Herausgabe)" suffix in the backend document-edit '
            . 'Publikationstyp dropdown (#2048), matching the landing page\'s already suffix-free labels.';
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
            ->getConnectionForTable('tx_dpf_domain_model_documenttype');

        return $connection->executeQuery(
            'SELECT uid, name, display_name FROM tx_dpf_domain_model_documenttype WHERE deleted = 0 AND name IN ('
                . implode(',', array_fill(0, count(self::AFFECTED_NAMES), '?'))
                . ')',
            self::AFFECTED_NAMES
        )->fetchAll();
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_domain_model_documenttype');

        if (!$connection->getSchemaManager()->tablesExist(['tx_dpf_domain_model_documenttype'])) {
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
            ->getConnectionForTable('tx_dpf_domain_model_documenttype');

        foreach ($this->findAffectedRows() as $row) {
            $fix = $this->computeFix($row);
            if (!empty($fix)) {
                $connection->update('tx_dpf_domain_model_documenttype', $fix, ['uid' => $row['uid']]);
            }
        }

        return true;
    }
}
