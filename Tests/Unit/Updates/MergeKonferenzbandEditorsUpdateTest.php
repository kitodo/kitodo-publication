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

use EWW\Dpf\Updates\MergeKonferenzbandEditorsUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class MergeKonferenzbandEditorsUpdateTest extends UnitTestCase
{
    /** Real uid-307 wrap shape as of 2026-09-03, fetched from sdvqucosa-test-web01. */
    private const REAL_WRAP = "key.wrap = <dt>|</dt>\r\n"
        . "value.if.equals.field = type\r\n"
        . "value.if.value = in_proceeding\r\n"
        . "value.noTrimWrap = || \r\n"
        . "value.dataWrap = {field:original_title}\r\n"
        . "value.dataWrap.typolink.parameter = {field:host_url}\r\n"
        . "value.dataWrap.typolink.parameter.fieldRequired = host_url\r\n"
        . "\r\n"
        . "value.append = TEXT\r\n"
        . "value.append {\r\n"
        . "\tvalue = {field:original_subtitle}\r\n"
        . "\tvalue.insertData = 1\r\n"
        . "\tvalue.noTrimWrap = | : | |\r\n"
        . "\tvalue.noTrimWrap.fieldRequired = original_subtitle\r\n"
        . "\twrap = |<br />\r\n"
        . "}\r\n"
        . "value.append.append = TEXT\r\n"
        . "value.append.append {\r\n"
        . "\tvalue = {field:original_publisher}\r\n"
        . "\tvalue.insertData = 1\r\n"
        . "\tvalue.noTrimWrap = |Herausgeber: | <br />\r\n"
        . "\tvalue.noTrimWrap.fieldRequired = original_publisher\r\n"
        . "}\r\n"
        . "value.append.append.append = TEXT\r\n"
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
        . "}\r\n"
        . "value.append.append.append.append.append = TEXT\r\n"
        . "value.append.append.append.append.append {\r\n"
        . "\tvalue = {field:original_place}\r\n"
        . "\tvalue.insertData = 1\r\n"
        . "\tvalue.noTrimWrap = |Erscheinungsort: | <br />\r\n"
        . "\tvalue.noTrimWrap.fieldRequired = original_place\r\n"
        . "}\r\n"
        . "value.append.append.append.append.append.append = TEXT\r\n"
        . "value.append.append.append.append.append.append {\r\n"
        . "\tvalue = {field:publisher0}\r\n"
        . "\tvalue.insertData = 1\r\n"
        . "\tvalue.noTrimWrap = |Verlag: | <br />\r\n"
        . "\tvalue.noTrimWrap.fieldRequired = publisher0\r\n"
        . "}\r\n"
        . "value.append.append.append.append.append.append.append = TEXT\r\n"
        . "value.append.append.append.append.append.append.append {\r\n"
        . "\tvalue = {field:original_doi}\r\n"
        . "\tvalue.insertData = 1\r\n"
        . "\tvalue.noTrimWrap = |DOI: | <br />\r\n"
        . "\tvalue.noTrimWrap.fieldRequired = original_doi\r\n"
        . "\r\n"
        . "}\r\n"
        . "value.wrap3 = <dd>|</dd>";

    public function testMergesThreeEditorBlocksIntoOneLabel()
    {
        $wizard = new MergeKonferenzbandEditorsUpdate();

        $fix = $wizard->computeFix(['uid' => 307, 'index_name' => 'original_in_proceeding0000000', 'wrap' => self::REAL_WRAP]);

        $this->assertArrayHasKey('wrap', $fix);
        $this->assertStringContainsString('Herausgegeben von: |', $fix['wrap']);
        $this->assertSame(1, substr_count($fix['wrap'], 'Herausgegeben von'));
        $this->assertStringNotContainsString('Herausgeber: |', $fix['wrap']);
    }

    public function testEditorBlockOrderIsAnchorPrefixPrefixBreak()
    {
        $wizard = new MergeKonferenzbandEditorsUpdate();

        $fix = $wizard->computeFix(['uid' => 307, 'index_name' => 'original_in_proceeding0000000', 'wrap' => self::REAL_WRAP]);

        $labelPos = strpos($fix['wrap'], 'original_publisher}');
        $sep1Pos = strpos($fix['wrap'], 'original_publisher_1}');
        $sep2Pos = strpos($fix['wrap'], 'original_publisher_2}');
        $brPos = strpos($fix['wrap'], 'value = <br />');

        $this->assertNotFalse($labelPos);
        $this->assertNotFalse($sep1Pos);
        $this->assertNotFalse($sep2Pos);
        $this->assertNotFalse($brPos);
        $this->assertLessThan($sep1Pos, $labelPos);
        $this->assertLessThan($sep2Pos, $sep1Pos);
        $this->assertLessThan($brPos, $sep2Pos);
    }

    public function testSubsequentFieldsSurviveUnchangedAfterRenumbering()
    {
        $wizard = new MergeKonferenzbandEditorsUpdate();

        $fix = $wizard->computeFix(['uid' => 307, 'index_name' => 'original_in_proceeding0000000', 'wrap' => self::REAL_WRAP]);

        $this->assertStringContainsString('Erscheinungsort: | <br />', $fix['wrap']);
        $this->assertStringContainsString('field:original_place', $fix['wrap']);
        $this->assertStringContainsString('Verlag: | <br />', $fix['wrap']);
        $this->assertStringContainsString('field:publisher0', $fix['wrap']);
        $this->assertStringContainsString('DOI: | <br />', $fix['wrap']);
        $this->assertStringContainsString('field:original_doi', $fix['wrap']);
    }

    public function testAlreadyFixedRowNeedsNoFix()
    {
        $wizard = new MergeKonferenzbandEditorsUpdate();

        $firstFix = $wizard->computeFix(['uid' => 307, 'index_name' => 'original_in_proceeding0000000', 'wrap' => self::REAL_WRAP]);

        $fix = $wizard->computeFix([
            'uid' => 307,
            'index_name' => 'original_in_proceeding0000000',
            'wrap' => $firstFix['wrap'],
        ]);

        $this->assertSame([], $fix);
    }

    public function testUnrelatedRowNeedsNoFix()
    {
        $wizard = new MergeKonferenzbandEditorsUpdate();

        $fix = $wizard->computeFix(['uid' => 999, 'index_name' => 'original_in_book', 'wrap' => self::REAL_WRAP]);

        $this->assertSame([], $fix);
    }
}
