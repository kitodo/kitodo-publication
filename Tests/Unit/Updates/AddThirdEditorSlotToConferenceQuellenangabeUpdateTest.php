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

use EWW\Dpf\Updates\AddThirdEditorSlotToConferenceQuellenangabeUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddThirdEditorSlotToConferenceQuellenangabeUpdateTest extends UnitTestCase
{
    // Actual tx_dpf_metadata.wrap content for original_in_proceeding0000000
    // (uid on test: verified against the live row before this wizard shipped).
    private const CURRENT_WRAP = <<<'EOT'
key.wrap = <dt>|</dt>
value.if.equals.field = type
value.if.value = in_proceeding
value.noTrimWrap = ||
value.dataWrap = {field:original_title}
value.dataWrap.typolink.parameter = {field:host_url}
value.dataWrap.typolink.parameter.fieldRequired = host_url

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
	value = {field:original_place}
	value.insertData = 1
	value.noTrimWrap = |Erscheinungsort: | <br />
	value.noTrimWrap.fieldRequired = original_place
}
value.append.append.append.append.append = TEXT
value.append.append.append.append.append {
	value = {field:publisher0}
	value.insertData = 1
	value.noTrimWrap = |Verlag: | <br />
	value.noTrimWrap.fieldRequired = publisher0
}
value.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append {
	value = {field:original_date}
	value.insertData = 1
	value.replacement.1.search = /^([0-9]{4}).*/
	value.replacement.1.replace = $1
	value.replacement.1.useRegExp = 1
	value.insertData = 1
	value.noTrimWrap = |Erscheinungsjahr: | <br />
	value.noTrimWrap.fieldRequired = original_date
}
value.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append {
	value = {field:series_title0}
	value.insertData = 1
	value.noTrimWrap = |Titel Schriftenreihe: | <br />
	value.noTrimWrap.fieldRequired = series_title0
}
value.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append {
	value = {field:original_volume}
	value.insertData = 1
	value.noTrimWrap = |Bandnummer Schriftenreihe: | <br />
	value.noTrimWrap.fieldRequired = original_volume
}
value.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append {
	value = {field:original_edition}
	value.insertData = 1
	value.noTrimWrap = | |
	value.noTrimWrap.fieldRequired = original_edition
}
value.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_issue}
	value.insertData = 1
	value.noTrimWrap = |Heft: || <br />
	value.noTrimWrap.fieldRequired = original_issue
}
value.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_pages}
	value.insertData = 1
	value.noTrimWrap = |Seiten:  |
	value.noTrimWrap.fieldRequired = original_pages
}
value.append.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_pages2}
	value.insertData = 1
	value.noTrimWrap = |–| <br />
	value.noTrimWrap.fieldRequired = original_pages2
}
value.append.append.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_issn}
	value.insertData = 1
	value.noTrimWrap = |ISSN: | <br />
	value.noTrimWrap.fieldRequired = original_issn
}
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_isbn}
	value.insertData = 1
	value.noTrimWrap = |ISBN: | <br />
	value.noTrimWrap.fieldRequired = original_isbn
}
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_articleid}
	value.insertData = 1
	value.noTrimWrap = |Artikelnummer: | <br />
	value.noTrimWrap.fieldRequired = original_articleid
}
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append = TEXT
value.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append {
	value = {field:original_doi}
	value.insertData = 1
	value.noTrimWrap = |DOI: | <br />
	value.noTrimWrap.fieldRequired = original_doi

}
value.wrap3 = <dd>|</dd>
EOT;

    public function testInsertsThirdEditorSlotAndRenumbersLaterFields()
    {
        $wizard = new AddThirdEditorSlotToConferenceQuellenangabeUpdate();

        $fixed = $wizard->computeFix(str_replace("\n", "\r\n", self::CURRENT_WRAP));

        $this->assertNotNull($fixed);
        $this->assertStringContainsString(
            "value.append.append.append.append = TEXT\r\n"
            . "value.append.append.append.append {\r\n"
            . "\tvalue = {field:original_publisher_2}\r\n"
            . "\tvalue.insertData = 1\r\n"
            . "\tvalue.noTrimWrap = |Herausgeber: | <br />\r\n"
            . "\tvalue.noTrimWrap.fieldRequired = original_publisher_2\r\n"
            . "}",
            $fixed
        );
        // original_place shifts from 4 to 5 .append levels.
        $this->assertStringContainsString(
            "value.append.append.append.append.append = TEXT\r\n"
            . "value.append.append.append.append.append {\r\n"
            . "\tvalue = {field:original_place}",
            $fixed
        );
        // The tail (original_doi) shifts from 16 to 17 .append levels.
        $this->assertStringContainsString(
            "value.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append.append {\r\n"
            . "\tvalue = {field:original_doi}",
            $fixed
        );
        // Brace-balanced.
        $this->assertSame(substr_count($fixed, '{'), substr_count($fixed, '}'));
    }

    public function testAlreadyFixedWrapProducesNoChange()
    {
        $wizard = new AddThirdEditorSlotToConferenceQuellenangabeUpdate();

        $already = str_replace("\n", "\r\n", self::CURRENT_WRAP);
        $fixed = $wizard->computeFix($already);
        $reFixed = $wizard->computeFix($fixed);

        $this->assertNull($reFixed);
    }

    public function testUnrelatedWrapProducesNoChange()
    {
        $wizard = new AddThirdEditorSlotToConferenceQuellenangabeUpdate();

        $fix = $wizard->computeFix("key.wrap = <dt>|</dt>\r\nvalue.dataWrap = {field:title}");

        $this->assertNull($fix);
    }
}
