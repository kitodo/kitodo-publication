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
 * Adds the last missing type-specific "Quellenangabe" source-citation row to
 * tx_dpf_metadata, closing out #2042: "Blog" for
 * type=partOfADynamicWebResource. Type value and host relatedItem field
 * shape both verified against the live test fixture `ubl-26-5048` (created
 * for this ticket after the row was originally left out of
 * AddTypeSpecificSourceCitationRowsUpdate for lack of a fixture). The host
 * shape matches the existing "Erschienen in" (contributionToPeriodical) row
 * field-for-field, so the wrap is copied from there with only the type
 * condition and label swapped.
 *
 * Idempotent: only inserts when no non-deleted row with this index_name
 * exists yet.
 */
class AddBlogSourceCitationRowUpdate implements UpgradeWizardInterface
{
    const TABLE = 'tx_dpf_metadata';
    const INDEX_NAME = 'original_in_dynamicwebresource';

    const WRAP = <<<'TS'
key.wrap = <dt>|</dt>
value.if.equals.field = type
value.if.value = partOfADynamicWebResource
value.noTrimWrap = | |
value.fieldRequired = original_title
value.dataWrap = {field:original_title}
value.dataWrap.typolink.parameter = /id/{field:multivolume_local}
value.dataWrap.typolink.parameter.fieldRequired = multivolume_local

value.append = TEXT
value.append {
	value = {field:original_subtitle}
	value.insertData = 1
	value.noTrimWrap = |: |<br />
	value.noTrimWrap.fieldRequired = original_subtitle

}
value.append.append = TEXT
value.append.append {
	value = {field:original_publisher}
	value.insertData = 1
	value.noTrimWrap = |Herausgeber: | <br />
	value.noTrimWrap.fieldRequired = original_publisher
	wrap = |<br />
}
value.append.append.append = TEXT
value.append.append.append {
	value = {field:original_place}
	value.insertData = 1
	value.noTrimWrap = |Erscheinungsort: | <br />
	value.noTrimWrap.fieldRequired = original_place
}
value.append.append.append.append = TEXT
value.append.append.append.append {
	value = {field:publisher0}
	value.insertData = 1
	value.noTrimWrap = |Verlag: | <br />
	value.noTrimWrap.fieldRequired = publisher0
}
value.append.append.append.append.append = TEXT
value.append.append.append.append.append {
	value = {field:original_date}
	value.insertData = 1
	value.replacement.1.search = /^([0-9]{4}).*/
	value.replacement.1.replace = $1
	value.replacement.1.useRegExp = 1
	value.insertData = 1
	value.noTrimWrap = |Erscheinungsjahr: | <br />
	value.noTrimWrap.fieldRequired = original_date
}
value.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append {
	value = {field:original_volume}
	value.insertData = 1
	value.noTrimWrap = |Jahrgang: | <br />
	value.noTrimWrap.fieldRequired = original_volume
}
value.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append {
	value = {field:original_edition}
	value.insertData = 1
	value.noTrimWrap = |Auflage: | <br />
	value.noTrimWrap.fieldRequired = original_edition
}
value.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append {
	value = {field:original_issue}
	value.insertData = 1
	value.noTrimWrap = |Heft: | <br />
	value.noTrimWrap.fieldRequired = original_issue
}
value.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append {
	value = {field:original_pages}
	value.insertData = 1
	value.noTrimWrap = <br />|Seiten:  |
	value.noTrimWrap.fieldRequired = original_pages
}
value.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_pages2}
	value.insertData = 1
	value.noTrimWrap = |–| <br />
	value.noTrimWrap.fieldRequired = original_pages2

}
value.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_issn}
	value.insertData = 1
	value.noTrimWrap = |ISSN: | <br />
	value.noTrimWrap.fieldRequired = original_issn
}
value.append.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_eissn}
	value.insertData = 1
	value.noTrimWrap = |E-ISSN: | <br />
	value.noTrimWrap.fieldRequired = original_eissn
}
value.append.append.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_isbn}
	value.insertData = 1
	value.noTrimWrap = |ISBN: | <br />
	value.noTrimWrap.fieldRequired = original_isbn
}
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_doi}
	value.insertData = 1
	value.setContentToCurrent = 1
	value.noTrimWrap = |DOI: | <br />
	value.noTrimWrap.fieldRequired = original_doi
}
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_article}
	value.insertData = 1
	value.noTrimWrap = |Artikelnummer: || <br />
	value.noTrimWrap.fieldRequired = original_article
}
value.wrap3 = <dd>|</dd>
TS;

    public function getIdentifier(): string
    {
        return 'dpfAddBlogSourceCitationRow';
    }

    public function getTitle(): string
    {
        return 'Add Blog source-citation row to tx_dpf_metadata';
    }

    public function getDescription(): string
    {
        return 'Adds the "Blog" type-conditioned Quellenangabe header row per #2042 '
            . '(type=partOfADynamicWebResource), with an English overlay labelled "Source". '
            . 'Closes the last row of #2042\'s table, deferred earlier for lack of a test fixture.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * The row definition this wizard inserts. Pure, for unit testing.
     *
     * @return array{index_name: string, label: string, wrap: string, sorting: int, overlay_sorting: int}
     */
    public function getNewRow(): array
    {
        return [
            'index_name' => self::INDEX_NAME,
            'label' => 'Blog',
            'wrap' => self::WRAP,
            'sorting' => 30390,
            'overlay_sorting' => 30260,
        ];
    }

    public function rowExists(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE);

        return $connection->count(
            'uid',
            self::TABLE,
            ['index_name' => self::INDEX_NAME, 'deleted' => 0]
        ) > 0;
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE);

        if (!$connection->getSchemaManager()->tablesExist([self::TABLE])) {
            return false;
        }

        return !$this->rowExists();
    }

    public function executeUpdate(): bool
    {
        if ($this->rowExists()) {
            return true;
        }

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE);

        $row = $this->getNewRow();
        $now = time();
        $connection->insert(self::TABLE, [
            'pid' => 1,
            'tstamp' => $now,
            'crdate' => $now,
            'index_name' => $row['index_name'],
            'label' => $row['label'],
            'wrap' => $row['wrap'],
            'sorting' => $row['sorting'],
        ]);
        $parentUid = (int)$connection->lastInsertId(self::TABLE);

        $connection->insert(self::TABLE, [
            'pid' => 1,
            'tstamp' => $now,
            'crdate' => $now,
            'sys_language_uid' => 1,
            'l18n_parent' => $parentUid,
            'index_name' => $row['index_name'],
            'label' => 'Source',
            'wrap' => $row['wrap'],
            'sorting' => $row['overlay_sorting'],
        ]);

        return true;
    }
}
