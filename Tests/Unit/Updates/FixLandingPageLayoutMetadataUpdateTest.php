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

use EWW\Dpf\Updates\FixLandingPageLayoutMetadataUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class FixLandingPageLayoutMetadataUpdateTest extends UnitTestCase
{
    /**
     * Reproduces the qucosa-82985 gap: absent funder 2-10 sub-fields each
     * emitted a literal <br/> via ifNull/ifEmpty, and that non-empty content
     * let the funder separator "wrap = <hr>" fire despite required = 1.
     * Stripping the ifNull/ifEmpty lines removes the <br/> junk and lets
     * required = 1 suppress the <hr> for absent funders.
     */
    public function testStripsIfNullAndIfEmptyBrLinesFromFundingWrap()
    {
        $wizard = new FixLandingPageLayoutMetadataUpdate();

        $wrap = "key.wrap = <dt>|</dt>\r\n"
            . "value.append = TEXT\r\n"
            . "value.append {\r\n"
            . "\tvalue = {field:funder_acronym}\r\n"
            . "\tnoTrimWrap = | (|) <br />\r\n"
            . "\tifNull = <br/>\r\n"
            . "\tifEmpty = <br/>\r\n"
            . "}\r\n"
            . "value.append.append = TEXT\r\n"
            . "value.append.append {\r\n"
            . "    wrap = <hr>\r\n"
            . "    required = 1\r\n"
            . "    ifNull = <br/>\r\n"
            . "}\r\n"
            . "value.wrap3 = <dd>|</dd>";

        $fix = $wizard->computeFix([
            'index_name' => 'project_funding00000',
            'sys_language_uid' => 0,
            'l18n_parent' => 0,
            'sorting' => 112768,
            'wrap' => $wrap,
        ]);

        $expected = "key.wrap = <dt>|</dt>\r\n"
            . "value.append = TEXT\r\n"
            . "value.append {\r\n"
            . "\tvalue = {field:funder_acronym}\r\n"
            . "\tnoTrimWrap = | (|) <br />\r\n"
            . "}\r\n"
            . "value.append.append = TEXT\r\n"
            . "value.append.append {\r\n"
            . "    wrap = <hr>\r\n"
            . "    required = 1\r\n"
            . "}\r\n"
            . "value.wrap3 = <dd>|</dd>";

        $this->assertSame($expected, $fix['wrap']);
        $this->assertArrayNotHasKey('sorting', $fix);
    }

    public function testFundingWrapWithoutJunkNeedsNoFix()
    {
        $wizard = new FixLandingPageLayoutMetadataUpdate();

        $fix = $wizard->computeFix([
            'index_name' => 'project_funding00000',
            'sys_language_uid' => 0,
            'l18n_parent' => 0,
            'sorting' => 112768,
            'wrap' => "key.wrap = <dt>|</dt>\r\nvalue.wrap3 = <dd>|</dd>",
        ]);

        $this->assertSame([], $fix);
    }

    public function testIfNullLinesInOtherRowsAreLeftAlone()
    {
        $wizard = new FixLandingPageLayoutMetadataUpdate();

        $fix = $wizard->computeFix([
            'index_name' => 'original0000000000',
            'sys_language_uid' => 0,
            'l18n_parent' => 0,
            'sorting' => 50000,
            'wrap' => "value.append {\r\n\tifNull = <br/>\r\n}",
        ]);

        $this->assertSame([], $fix);
    }

    public function testTypeRowGetsDoctypeClassAndTopSorting()
    {
        $wizard = new FixLandingPageLayoutMetadataUpdate();

        $fix = $wizard->computeFix([
            'index_name' => 'type',
            'sys_language_uid' => 0,
            'l18n_parent' => 0,
            'sorting' => 115712,
            'wrap' => "key.wrap = <dt>|</dt>\r\nvalue.required = 1\r\nvalue.wrap = <dd>|</dd>",
        ]);

        $this->assertSame(
            "key.wrap = <dt class=\"doctype\">|</dt>\r\n"
            . "value.required = 1\r\n"
            . "value.wrap = <dd class=\"doctype\">|</dd>",
            $fix['wrap']
        );
        $this->assertSame(512, $fix['sorting']);
    }

    /**
     * The English overlay (l18n_parent > 0) carries its own byte-identical
     * wrap copy which getRecordOverlay() prefers, so it needs the class hooks
     * too — but sorting on overlay rows is unused (findRenderableFields
     * filters l18n_parent = 0) and must not be touched.
     */
    public function testTypeOverlayRowGetsClassButNoSortingChange()
    {
        $wizard = new FixLandingPageLayoutMetadataUpdate();

        $fix = $wizard->computeFix([
            'index_name' => 'type',
            'sys_language_uid' => 1,
            'l18n_parent' => 8,
            'sorting' => 114176,
            'wrap' => "key.wrap = <dt>|</dt>\r\nvalue.required = 1\r\nvalue.wrap = <dd>|</dd>",
        ]);

        $this->assertStringContainsString('<dt class="doctype">|</dt>', $fix['wrap']);
        $this->assertArrayNotHasKey('sorting', $fix);
    }

    public function testAlreadyFixedTypeRowNeedsNoFix()
    {
        $wizard = new FixLandingPageLayoutMetadataUpdate();

        $fix = $wizard->computeFix([
            'index_name' => 'type',
            'sys_language_uid' => 0,
            'l18n_parent' => 0,
            'sorting' => 512,
            'wrap' => "key.wrap = <dt class=\"doctype\">|</dt>\r\n"
                . "value.required = 1\r\n"
                . "value.wrap = <dd class=\"doctype\">|</dd>",
        ]);

        $this->assertSame([], $fix);
    }

    public function testUnrelatedRowNeedsNoFix()
    {
        $wizard = new FixLandingPageLayoutMetadataUpdate();

        $fix = $wizard->computeFix([
            'index_name' => 'title0',
            'sys_language_uid' => 0,
            'l18n_parent' => 0,
            'sorting' => 17408,
            'wrap' => "key.wrap = <dt class=\"title\">|</dt>\r\nvalue.required = 1\r\nvalue.wrap3 = <dd class=\"title\">|</dd>",
        ]);

        $this->assertSame([], $fix);
    }
}
