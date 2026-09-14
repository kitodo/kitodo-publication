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
 * Fixes #2047 (UBL-26-5007): LandingPageAssembler::injectNameIdentifierTooltips()
 * already builds authorIds1/2/3 and publisherIds1/2/3 pseudo-fields (position 1-3,
 * personal roles are capped there), but the "Authors" (uid 394) and "HerausgeberIn"
 * (uid 96) wrap chains only ever read authorIds1/publisherIds1 - position 1's
 * <dd>/<li> tag. Positions 2 and 3's tags carry no title="" attribute at all, so
 * their tooltip data is built and silently dropped. Institution roles
 * (corporation_author/_editor) already cover every position - only these two
 * personal-role rows have the gap.
 */
class AddPositionalTooltipsToAuthorPublisherUpdate implements UpgradeWizardInterface
{
    private const AUTHOR_INDEX_NAME = 'authors';
    private const PUBLISHER_INDEX_NAME = 'publisher';

    private const AUTHOR_REPLACEMENTS = [
        '<dd class="author">|<span class="affiliation" style="display:none;">{field:affiliation2}</span></dd>|'
            => '<dd class="author" title="{field:authorIds2}">|<span class="affiliation" style="display:none;">{field:affiliation2}</span></dd>|',
        '<dd class="author">|<span class="affiliation" style="display:none;">{field:affiliation3}</span></dd>|'
            => '<dd class="author" title="{field:authorIds3}">|<span class="affiliation" style="display:none;">{field:affiliation3}</span></dd>|',
    ];

    private const PUBLISHER_REPLACEMENTS = [
        "fieldRequired = publisher2\r\n\tvalue = {field:publisher2}\r\n\tvalue.insertData = 1\r\n\tvalue.wrap = <li>|</li>"
            => "fieldRequired = publisher2\r\n\tvalue = {field:publisher2}\r\n\tvalue.insertData = 1\r\n\tvalue.wrap = <li title=\"{field:publisherIds2}\">|</li>",
        "fieldRequired = publisher3\r\n\tvalue = {field:publisher3}\r\n\tvalue.insertData = 1\r\n\tvalue.wrap = <li>|</li>"
            => "fieldRequired = publisher3\r\n\tvalue = {field:publisher3}\r\n\tvalue.insertData = 1\r\n\tvalue.wrap = <li title=\"{field:publisherIds3}\">|</li>",
    ];

    public function getIdentifier(): string
    {
        return 'dpfAddPositionalTooltipsToAuthorPublisher';
    }

    public function getTitle(): string
    {
        return 'Add position 2/3 name-identifier tooltips to AutorIn/HerausgeberIn';
    }

    public function getDescription(): string
    {
        return 'The "Authors"/"HerausgeberIn" wrap chains only put a title="" tooltip on the first '
            . 'author/editor; injectNameIdentifierTooltips() already builds authorIds2/3 and '
            . 'publisherIds2/3, this wizard wires positions 2 and 3 to read them (#2047).';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * @param array $row of [uid, index_name, wrap]
     * @return array
     */
    public function computeFix(array $row): array
    {
        if ($row['index_name'] === self::AUTHOR_INDEX_NAME) {
            $replacements = self::AUTHOR_REPLACEMENTS;
        } elseif ($row['index_name'] === self::PUBLISHER_INDEX_NAME) {
            $replacements = self::PUBLISHER_REPLACEMENTS;
        } else {
            return [];
        }

        $wrap = (string) $row['wrap'];
        $changed = false;
        foreach ($replacements as $old => $new) {
            if (strpos($wrap, $old) !== false) {
                $wrap = str_replace($old, $new, $wrap);
                $changed = true;
            }
        }

        return $changed ? ['wrap' => $wrap] : [];
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
