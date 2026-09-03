<?php

namespace EWW\Dpf\Tests\Unit\Updates;

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

use EWW\Dpf\Updates\AddEditorSlotsToMediaContributionQuellenangabeUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddEditorSlotsToMediaContributionQuellenangabeUpdateTest extends UnitTestCase
{
    // Actual tx_dpf_metadata.wrap content for original_in_media (uid 537,
    // "Erschienen in" label) - verified against the live row before this
    // wizard shipped.
    private const CURRENT_WRAP = <<<'EOT'
key.wrap = <dt>|</dt>
value.if.equals.field = type
value.if.value = contributionToPeriodical
value.noTrimWrap = | |
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
EOT;

    public function testInsertsSecondAndThirdEditorSlotsAndRenumbersLaterFields()
    {
        $wizard = new AddEditorSlotsToMediaContributionQuellenangabeUpdate();

        $fixed = $wizard->computeFix(str_replace("\n", "\r\n", self::CURRENT_WRAP));

        $this->assertNotNull($fixed);
        $this->assertStringContainsString(
            "value.append.append.append = TEXT\r\n"
            . "value.append.append.append {\r\n"
            . "\tvalue = {field:original_publisher_1}\r\n"
            . "\tvalue.insertData = 1\r\n"
            . "\tvalue.noTrimWrap = |Herausgeber: | <br />\r\n"
            . "\tvalue.noTrimWrap.fieldRequired = original_publisher_1\r\n"
            . "}\r\n"
            . "value.append.append.append.append = TEXT\r\n"
            . "value.append.append.append.append {\r\n"
            . "\tvalue = {field:original_publisher_2}\r\n"
            . "\tvalue.insertData = 1\r\n"
            . "\tvalue.noTrimWrap = |Herausgeber: | <br />\r\n"
            . "\tvalue.noTrimWrap.fieldRequired = original_publisher_2\r\n"
            . "}",
            $fixed
        );
        // original_place shifts from 3 to 5 .append levels (two slots inserted).
        $this->assertStringContainsString(
            "value.append.append.append.append.append = TEXT\r\n"
            . "value.append.append.append.append.append {\r\n"
            . "\tvalue = {field:original_place}",
            $fixed
        );
        // The tail (original_article) shifts from 15 to 17 .append levels.
        $this->assertStringContainsString(
            "value.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append {\r\n"
            . "\tvalue = {field:original_article}",
            $fixed
        );
        // Brace-balanced.
        $this->assertSame(substr_count($fixed, '{'), substr_count($fixed, '}'));
        // The trailing "wrap = |<br />" that made this doctype's block shape
        // differ from the Konferenzband one must survive untouched.
        $this->assertStringContainsString("wrap = |<br />", $fixed);
    }

    public function testAlreadyFixedWrapProducesNoChange()
    {
        $wizard = new AddEditorSlotsToMediaContributionQuellenangabeUpdate();

        $already = str_replace("\n", "\r\n", self::CURRENT_WRAP);
        $fixed = $wizard->computeFix($already);
        $reFixed = $wizard->computeFix($fixed);

        $this->assertNull($reFixed);
    }

    public function testUnrelatedWrapProducesNoChange()
    {
        $wizard = new AddEditorSlotsToMediaContributionQuellenangabeUpdate();

        $fix = $wizard->computeFix("key.wrap = <dt>|</dt>\r\nvalue.dataWrap = {field:title}");

        $this->assertNull($fix);
    }
}
