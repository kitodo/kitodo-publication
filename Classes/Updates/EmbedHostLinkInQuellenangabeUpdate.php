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
 * Fixes #2039 item 2: for Zeitschriftenartikel (type=article) and
 * Konferenzbeitrag (type=in_proceeding), the "Quellenangabe" prose row
 * ("Zeitschrift"/"Konferenzband", tx_dpf_metadata uid 313/307) links its
 * title via `/id/{field:multivolume_local}` — but that field's xpath only
 * matches a `mods:identifier[@type="local"]` on the host relatedItem, which
 * these records don't carry (only a URN, see qucosa-83142/qucosa-14455).
 * The title rendered unlinked, duplicating the separately-linked host entry
 * LandingPageAssembler::getParentItems() renders below.
 *
 * Points the typolink at `{field:host_url}` instead — a pseudo-field
 * LandingPageController now injects from
 * LandingPageAssembler::getHostUrl(getParentItems()), which already builds
 * the correct absolute URL for both local- and URN-identified hosts. The
 * controller also drops the now-redundant parentItems host entry for these
 * two doctypes via filterEmbeddedHostItems().
 */
class EmbedHostLinkInQuellenangabeUpdate implements UpgradeWizardInterface
{
    private const AFFECTED_INDEX_NAMES = ['original0000000000', 'original_in_proceeding0000000'];

    private const OLD_PARAMETER = 'value.dataWrap.typolink.parameter = /id/{field:multivolume_local}';
    private const NEW_PARAMETER = 'value.dataWrap.typolink.parameter = {field:host_url}';

    private const OLD_FIELD_REQUIRED = 'value.dataWrap.typolink.parameter.fieldRequired = multivolume_local';
    private const NEW_FIELD_REQUIRED = 'value.dataWrap.typolink.parameter.fieldRequired = host_url';

    public function getIdentifier(): string
    {
        return 'dpfEmbedHostLinkInQuellenangabe';
    }

    public function getTitle(): string
    {
        return 'Link the Quellenangabe title to the host document (Zeitschrift/Konferenzband)';
    }

    public function getDescription(): string
    {
        return 'Rewrites the title link on the "Zeitschrift"/"Konferenzband" Quellenangabe rows '
            . '(tx_dpf_metadata) to use the {field:host_url} pseudo-field LandingPageController now '
            . 'injects, instead of the unpopulated multivolume_local xpath, so the citation shows '
            . 'one linked title instead of an unlinked line plus a separate linked entry.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * Given a tx_dpf_metadata row, returns the column/value pairs it needs
     * updated, or [] if the row is already fine / not affected.
     *
     * @param array $row of [uid, index_name, wrap]
     * @return array
     */
    public function computeFix(array $row): array
    {
        if (!in_array($row['index_name'], self::AFFECTED_INDEX_NAMES, true)) {
            return [];
        }

        $wrap = (string) $row['wrap'];
        if (strpos($wrap, self::OLD_PARAMETER) === false) {
            return [];
        }

        $wrap = str_replace(self::OLD_PARAMETER, self::NEW_PARAMETER, $wrap);
        $wrap = str_replace(self::OLD_FIELD_REQUIRED, self::NEW_FIELD_REQUIRED, $wrap);

        return ['wrap' => $wrap];
    }

    protected function findAffectedRows(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_dpf_metadata');

        return $connection->executeQuery(
            'SELECT uid, index_name, wrap FROM tx_dpf_metadata WHERE deleted = 0 AND index_name IN (?, ?)',
            self::AFFECTED_INDEX_NAMES
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
