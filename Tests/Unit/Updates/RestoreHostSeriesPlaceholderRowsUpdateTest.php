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

use EWW\Dpf\Updates\RestoreHostSeriesPlaceholderRowsUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class RestoreHostSeriesPlaceholderRowsUpdateTest extends UnitTestCase
{
    /** Real uid-337 (series0) wrap as of 2026-09-04, fetched from sdvqucosa-test-web01 - has value.wrap3. */
    private const SERIES0_WRAP = "key.wrap = <dt>|</dt>\r\n"
        . "value.fieldRequired = series_title\r\n"
        . "value.dataWrap = {field:series_title}\r\n"
        . "value.dataWrap.typolink.parameter = /id/{field:series_local}\r\n"
        . "value.dataWrap.typolink.parameter.fieldRequired = series_local\r\n"
        . "\r\n"
        . "value.wrap3 = <dd>|</dd>";

    /** Real uid-507 (multivolume_proceeding) wrap - no trailing value.wrap3. */
    private const MULTIVOLUME_PROCEEDING_WRAP = "key.wrap = <dt>|</dt>\r\n"
        . "value.if.equals.field = type\r\n"
        . "value.if.value = proceeding\r\n"
        . "value.fieldRequired = multivolume_title\r\n"
        . "value.dataWrap = {field:multivolume_title}\r\n"
        . "value.dataWrap.typolink.parameter = /id/{field:multivolume_local}\r\n"
        . "value.dataWrap.typolink.parameter.fieldRequired = multivolume_local";

    public function testStripsSeries0DownToKeyWrapOnly()
    {
        $wizard = new RestoreHostSeriesPlaceholderRowsUpdate();

        $fix = $wizard->computeFix(['uid' => 337, 'index_name' => 'series0', 'wrap' => self::SERIES0_WRAP]);

        $this->assertSame('key.wrap = <dt>|</dt>', $fix['wrap']);
    }

    public function testStripsMultivolumeProceedingDownToKeyWrapOnly()
    {
        $wizard = new RestoreHostSeriesPlaceholderRowsUpdate();

        $fix = $wizard->computeFix([
            'uid' => 507,
            'index_name' => 'multivolume_proceeding',
            'wrap' => self::MULTIVOLUME_PROCEEDING_WRAP,
        ]);

        $this->assertSame('key.wrap = <dt>|</dt>', $fix['wrap']);
    }

    /**
     * value.wrap3 alone must not survive: TYPO3 stdWrap's wrap3 applies
     * unconditionally, so "value.wrap3 = <dd>|</dd>" on an otherwise-empty
     * value still produces the non-empty string "<dd></dd>" - defeating the
     * whole point of emptying the row (caught live, see class doc comment).
     */
    public function testStripsDanglingValueWrap3TooNotJustDataWrap()
    {
        $wizard = new RestoreHostSeriesPlaceholderRowsUpdate();

        $fix = $wizard->computeFix([
            'uid' => 337,
            'index_name' => 'series0',
            'wrap' => "key.wrap = <dt>|</dt>\r\nvalue.wrap3 = <dd>|</dd>",
        ]);

        $this->assertSame('key.wrap = <dt>|</dt>', $fix['wrap']);
    }

    public function testAlreadyEmptyRowNeedsNoFix()
    {
        $wizard = new RestoreHostSeriesPlaceholderRowsUpdate();

        $fix = $wizard->computeFix([
            'uid' => 337,
            'index_name' => 'series0',
            'wrap' => 'key.wrap = <dt>|</dt>',
        ]);

        $this->assertSame([], $fix);
    }

    public function testUnrelatedRowNeedsNoFix()
    {
        $wizard = new RestoreHostSeriesPlaceholderRowsUpdate();

        $fix = $wizard->computeFix([
            'uid' => 537,
            'index_name' => 'original_in_media',
            'wrap' => self::MULTIVOLUME_PROCEEDING_WRAP,
        ]);

        $this->assertSame([], $fix);
    }
}
