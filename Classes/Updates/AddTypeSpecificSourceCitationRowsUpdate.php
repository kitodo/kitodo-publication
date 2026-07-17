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
 * Adds the two missing type-specific "Quellenangabe" source-citation rows to
 * tx_dpf_metadata (#2042):
 *
 * - "Nachschlagewerk" for type=in_encyclopedia (type value verified against
 *   the live extraction of ubl-26-5002); wrap copied from the
 *   contained_work/"Sammelband" row.
 * - "Erschienen in" for type=contributionToPeriodical (verified against
 *   ubl-pubman-420141); wrap copied from the article/"Zeitschrift" row.
 *
 * The Blog row from #2042's table stays out of scope: no test fixture exists
 * yet ("Testbeispiel muss noch erstellt werden"), so its type value cannot
 * be verified.
 *
 * Each new row gets a sys_language_uid=1 overlay labelled "Source", matching
 * the three existing type-conditioned rows (which keep their German inner
 * labels in the English overlay wrap as well). Idempotent: a row is only
 * inserted when no non-deleted row with the same index_name exists, so the
 * wizard is safe to re-run on any environment.
 */
class AddTypeSpecificSourceCitationRowsUpdate implements UpgradeWizardInterface
{
    const TABLE = 'tx_dpf_metadata';

    const WRAP_IN_ENCYCLOPEDIA = <<<'TS'
key.wrap = <dt>|</dt>
value.if.equals.field = type
value.if.value = in_encyclopedia
value.noTrimWrap = || 
value.fieldRequired = original_title
value.dataWrap = {field:original_title}
value.dataWrap.typolink.parameter = /id/{field:multivolume_local}
value.dataWrap.typolink.parameter.fieldRequired = multivolume_local

value.append = TEXT
value.append {
	value = {field:original_subtitle}
	value.insertData = 1
	value.noTrimWrap = | : | |
	value.noTrimWrap.fieldRequired = original_subtitle
	wrap = |<br />
}
value.append.append = TEXT
value.append.append {
	value = {field:original_publisher}
	value.insertData = 1
	value.noTrimWrap = |Herausgeber: | <br />
	value.noTrimWrap.fieldRequired = original_publisher
}
value.append.append.append = TEXT
value.append.append.append {
	value = {field:original_publisher_1}
	value.insertData = 1
	value.noTrimWrap = |Herausgeber: | <br />
	value.noTrimWrap.fieldRequired = original_publisher_1
}
value.append.append.append.append = TEXT
value.append.append.append.append {
	value = {field:original_publisher_2}
	value.insertData = 1
	value.noTrimWrap = |Herausgeber: | <br />
	value.noTrimWrap.fieldRequired = original_publisher_2
}
value.append.append.append.append.append = TEXT
value.append.append.append.append.append {
	value = {field:original_place}
	value.insertData = 1
	value.noTrimWrap = |Erscheinungsort: | <br />
	value.noTrimWrap.fieldRequired = original_place
}
value.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append {
	value = {field:publisher0}
	value.insertData = 1
	value.noTrimWrap = |Verlag: | <br />
	value.noTrimWrap.fieldRequired = publisher0
}
value.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append {
	value = {field:original_date}
	value.insertData = 1
	value.replacement.1.search = /^([0-9]{4}).*/
	value.replacement.1.replace = $1
	value.replacement.1.useRegExp = 1
	value.insertData = 1
	value.noTrimWrap = |Erscheinungsjahr: | <br />
	value.noTrimWrap.fieldRequired = original_date
}
value.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append {
	value = {field:series_title0}
	value.insertData = 1
	value.noTrimWrap = |Titel Schriftenreihe: | <br />
	value.noTrimWrap.fieldRequired = series_title0
}
value.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append {
	value = {field:original_volume}
	value.insertData = 1
	value.noTrimWrap = |Bandnummer Schriftenreihe: | <br />
	value.noTrimWrap.fieldRequired = original_volume
}
value.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_edition}
	value.insertData = 1
	value.noTrimWrap = | |
	value.noTrimWrap.fieldRequired = original_edition
}
value.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_issue}
	value.insertData = 1
	value.noTrimWrap = |Heft: || <br />
	value.noTrimWrap.fieldRequired = original_issue
}
value.append.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_pages}
	value.insertData = 1
	value.noTrimWrap = |Seiten:  |
	value.noTrimWrap.fieldRequired = original_pages
}
value.append.append.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_pages2}
	value.insertData = 1
	value.noTrimWrap = |–| <br />
	value.noTrimWrap.fieldRequired = original_pages2
}
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_issn}
	value.insertData = 1
	value.noTrimWrap = |ISSN: | <br />
	value.noTrimWrap.fieldRequired = original_issn
}
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_isbn}
	value.insertData = 1
	value.noTrimWrap = |ISBN: | <br />
	value.noTrimWrap.fieldRequired = original_isbn
}
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_eisbn}
	value.insertData = 1
	value.noTrimWrap = |E-ISBN: | <br />
	value.noTrimWrap.fieldRequired = original_eisbn
}
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_doi}
	value.insertData = 1
	value.noTrimWrap = |DOI: | <br />
	value.noTrimWrap.fieldRequired = original_doi
}
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_article}
	value.insertData = 1
	value.noTrimWrap = |Artikelnummer: || <br />
	value.noTrimWrap.fieldRequired = original_article
}
value.wrap3 = <dd>|</dd>
TS;

    const WRAP_IN_MEDIA = <<<'TS'
key.wrap = <dt>|</dt>
value.if.equals.field = type
value.if.value = contributionToPeriodical
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
        return 'dpfAddTypeSpecificSourceCitationRows';
    }

    public function getTitle(): string
    {
        return 'Add Nachschlagewerk / Erschienen in source-citation rows to tx_dpf_metadata';
    }

    public function getDescription(): string
    {
        return 'Adds two type-conditioned Quellenangabe header rows per #2042: "Nachschlagewerk" '
            . '(type=in_encyclopedia) and "Erschienen in" (type=contributionToPeriodical), each '
            . 'with an English overlay labelled "Source". Rows are copies of the existing '
            . 'Sammelband/Zeitschrift wrap blocks with the type condition swapped.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    /**
     * The row definitions this wizard inserts. Pure, for unit testing.
     *
     * @return array[]
     */
    public function getNewRows(): array
    {
        return [
            [
                'index_name' => 'original_in_encyclopedia',
                'label' => 'Nachschlagewerk',
                'wrap' => self::WRAP_IN_ENCYCLOPEDIA,
                'sorting' => 31616,
                'overlay_sorting' => 31488,
            ],
            [
                'index_name' => 'original_in_media',
                'label' => 'Erschienen in',
                'wrap' => self::WRAP_IN_MEDIA,
                'sorting' => 30336,
                'overlay_sorting' => 30208,
            ],
        ];
    }

    protected function findMissingRows(): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE);

        $missing = [];
        foreach ($this->getNewRows() as $row) {
            $count = $connection->count(
                'uid',
                self::TABLE,
                ['index_name' => $row['index_name'], 'deleted' => 0]
            );
            if ($count === 0) {
                $missing[] = $row;
            }
        }

        return $missing;
    }

    public function updateNecessary(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE);

        if (!$connection->getSchemaManager()->tablesExist([self::TABLE])) {
            return false;
        }

        return !empty($this->findMissingRows());
    }

    public function executeUpdate(): bool
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE);

        $now = time();
        foreach ($this->findMissingRows() as $row) {
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
        }

        return true;
    }
}
