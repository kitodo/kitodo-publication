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

use EWW\Dpf\Updates\FixSammelbandHostLinkAndFieldOrderUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class FixSammelbandHostLinkAndFieldOrderUpdateTest extends UnitTestCase
{
    /** Real uid-308 wrap content as of 2026-09-03, fetched from sdvqucosa-test-web01. */
    private const REAL_WRAP = "key.wrap = <dt>|</dt>\r\n"
        . "value.if.equals.field = type\r\n"
        . "value.if.value = contained_work\r\n"
        . "value.noTrimWrap = || \r\n"
        . "value.dataWrap = {field:original_title}\r\n"
        . "value.dataWrap.typolink.parameter = /id/{field:multivolume_local}\r\n"
        . "value.dataWrap.typolink.parameter.fieldRequired = multivolume_local\r\n"
        . "\r\n"
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
        . "}\r\n";

    public function testRewritesTypolinkParameterOnSammelbandRow()
    {
        $wizard = new FixSammelbandHostLinkAndFieldOrderUpdate();

        $fix = $wizard->computeFix([
            'uid' => 308,
            'index_name' => 'original_in_book',
            'wrap' => self::REAL_WRAP,
        ]);

        $this->assertStringContainsString('value.dataWrap.typolink.parameter = {field:host_url}', $fix['wrap']);
        $this->assertStringContainsString(
            'value.dataWrap.typolink.parameter.fieldRequired = host_url',
            $fix['wrap']
        );
        $this->assertStringNotContainsString('multivolume_local', $fix['wrap']);
    }

    public function testMovesErscheinungsortAfterVerlag()
    {
        $wizard = new FixSammelbandHostLinkAndFieldOrderUpdate();

        $fix = $wizard->computeFix([
            'uid' => 308,
            'index_name' => 'original_in_book',
            'wrap' => self::REAL_WRAP,
        ]);

        $placePosition = strpos($fix['wrap'], '{field:original_place}');
        $publisherPosition = strpos($fix['wrap'], '{field:publisher0}');

        $this->assertNotFalse($placePosition);
        $this->assertNotFalse($publisherPosition);
        $this->assertGreaterThan($publisherPosition, $placePosition);

        // Field content moves, TypoScript append-depth paths stay put (no renumbering needed).
        $this->assertStringContainsString(
            "value.append.append.append.append.append {\r\n\tvalue = {field:publisher0}",
            $fix['wrap']
        );
        $this->assertStringContainsString(
            "value.append.append.append.append.append.append {\r\n\tvalue = {field:original_place}",
            $fix['wrap']
        );
    }

    public function testAlreadyFixedRowNeedsNoFix()
    {
        $wizard = new FixSammelbandHostLinkAndFieldOrderUpdate();

        $fix = $wizard->computeFix([
            'uid' => 308,
            'index_name' => 'original_in_book',
            'wrap' => $wizard->computeFix([
                'uid' => 308,
                'index_name' => 'original_in_book',
                'wrap' => self::REAL_WRAP,
            ])['wrap'],
        ]);

        $this->assertSame([], $fix);
    }

    public function testUnrelatedRowNeedsNoFix()
    {
        $wizard = new FixSammelbandHostLinkAndFieldOrderUpdate();

        $fix = $wizard->computeFix([
            'uid' => 307,
            'index_name' => 'original_in_proceeding0000000',
            'wrap' => self::REAL_WRAP,
        ]);

        $this->assertSame([], $fix);
    }
}
