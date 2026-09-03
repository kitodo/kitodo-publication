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
 * Fixes #2040 item 5: for Sammelband (type=contained_work), the
 * "Quellenangabe" prose row (tx_dpf_metadata uid 308,
 * index_name=original_in_book) links its title via
 * `/id/{field:multivolume_local}` — same defect class as #2039 item 2
 * (EmbedHostLinkInQuellenangabeUpdate), just never applied to this row.
 * Because `contained_work` isn't in
 * LandingPageAssembler::DOCTYPES_WITH_EMBEDDED_HOST_LINK, the host
 * relatedItem is instead spliced in separately as its own "Erschienen in"
 * block, so the page shows two groups for one relationship. This wizard
 * points the title link at `{field:host_url}` like the other Quellenangabe
 * rows already do; the code half (adding 'contained_work' to
 * DOCTYPES_WITH_EMBEDDED_HOST_LINK) ships alongside it in the same commit.
 *
 * Also reorders the row's Erscheinungsort/Verlag sub-fields: the ticket
 * wants Erscheinungsort (original_place) to render after Verlag
 * (publisher0), but the wrap chain currently has it before.
 */
class FixSammelbandHostLinkAndFieldOrderUpdate implements UpgradeWizardInterface
{
    private const INDEX_NAME = 'original_in_book';

    private const OLD_PARAMETER = 'value.dataWrap.typolink.parameter = /id/{field:multivolume_local}';
    private const NEW_PARAMETER = 'value.dataWrap.typolink.parameter = {field:host_url}';

    private const OLD_FIELD_REQUIRED = 'value.dataWrap.typolink.parameter.fieldRequired = multivolume_local';
    private const NEW_FIELD_REQUIRED = 'value.dataWrap.typolink.parameter.fieldRequired = host_url';

    private const PLACE_LINES = "\tvalue = {field:original_place}\r\n"
        . "\tvalue.insertData = 1\r\n"
        . "\tvalue.noTrimWrap = |Erscheinungsort: | <br />\r\n"
        . "\tvalue.noTrimWrap.fieldRequired = original_place";

    private const PUBLISHER_LINES = "\tvalue = {field:publisher0}\r\n"
        . "\tvalue.insertData = 1\r\n"
        . "\tvalue.noTrimWrap = |Verlag: | <br />\r\n"
        . "\tvalue.noTrimWrap.fieldRequired = publisher0";

    private const SWAP_PLACEHOLDER = '###FixSammelbandHostLinkAndFieldOrderUpdate-swap###';

    public function getIdentifier(): string
    {
        return 'dpfFixSammelbandHostLinkAndFieldOrder';
    }

    public function getTitle(): string
    {
        return 'Merge Sammelband "Erschienen in" into its Quellenangabe row and reorder Erscheinungsort/Verlag';
    }

    public function getDescription(): string
    {
        return 'Points the "Sammelband" Quellenangabe title link (tx_dpf_metadata uid 308) at the '
            . '{field:host_url} pseudo-field instead of the unpopulated multivolume_local xpath, so the '
            . 'host document shows as one linked citation instead of an unlinked line plus a separate '
            . '"Erschienen in" block. Also swaps the row\'s Erscheinungsort/Verlag sub-fields so Verlag '
            . 'renders first.';
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
        if ($row['index_name'] !== self::INDEX_NAME) {
            return [];
        }

        $wrap = (string) $row['wrap'];
        $fixed = $wrap;

        if (strpos($fixed, self::OLD_PARAMETER) !== false) {
            $fixed = str_replace(self::OLD_PARAMETER, self::NEW_PARAMETER, $fixed);
            $fixed = str_replace(self::OLD_FIELD_REQUIRED, self::NEW_FIELD_REQUIRED, $fixed);
        }

        $placePosition = strpos($fixed, self::PLACE_LINES);
        $publisherPosition = strpos($fixed, self::PUBLISHER_LINES);

        // Only swap while Erscheinungsort still precedes Verlag — both blocks' inner
        // lines are identical regardless of which append-depth slot holds them, so
        // re-running this after a successful swap must not toggle it back.
        if ($placePosition !== false && $publisherPosition !== false && $placePosition < $publisherPosition) {
            $fixed = str_replace(self::PLACE_LINES, self::SWAP_PLACEHOLDER, $fixed);
            $fixed = str_replace(self::PUBLISHER_LINES, self::PLACE_LINES, $fixed);
            $fixed = str_replace(self::SWAP_PLACEHOLDER, self::PUBLISHER_LINES, $fixed);
        }

        if ($fixed === $wrap) {
            return [];
        }

        return ['wrap' => $fixed];
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
