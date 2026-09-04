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

use EWW\Dpf\Updates\AddPreprintSourceCitationRowUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddPreprintSourceCitationRowUpdateTest extends UnitTestCase
{
    public function testMainRowShape()
    {
        $row = (new AddPreprintSourceCitationRowUpdate())->getMainRow();

        $this->assertSame('original_preprint', $row['index_name']);
        $this->assertSame('Preprint', $row['label']);
    }

    public function testOverlayRowIsMarkedAsLanguageOverlayOfMainRow()
    {
        $update = new AddPreprintSourceCitationRowUpdate();
        $overlay = $update->getOverlayRow(588);

        $this->assertSame('original_preprint', $overlay['index_name']);
        $this->assertSame('Source', $overlay['label']);
        $this->assertSame(1, $overlay['sys_language_uid']);
        $this->assertSame(588, $overlay['l18n_parent']);
    }

    public function testWrapGatesOnPreprintType()
    {
        $wrap = (new AddPreprintSourceCitationRowUpdate())->buildWrap();

        $this->assertStringContainsString('value.if.value = preprint', $wrap);
        $this->assertStringContainsString('{field:host_url}', $wrap);
    }

    public function testWrapSplicesAllExpectedFields()
    {
        $wrap = (new AddPreprintSourceCitationRowUpdate())->buildWrap();

        foreach ([
            'original_subtitle', 'original_place', 'original_date', 'publisher0',
            'original_author', 'original_author_1',
            'original_publisher', 'original_publisher_1',
            'original_translator', 'original_translator_1',
            'original_institution_author', 'original_institution_author_1',
            'original_institution_publisher', 'original_institution_publisher_1',
            'original_edition', 'original_volume', 'original_issue',
        ] as $field) {
            $this->assertStringContainsString('{field:' . $field . '}', $wrap);
            $this->assertStringContainsString('fieldRequired = ' . $field, $wrap);
        }
    }

    public function testAppendChainDepthIsSequentialNotDuplicated()
    {
        $wrap = (new AddPreprintSourceCitationRowUpdate())->buildWrap();

        // 17 fields => the last block's key is exactly 17 ".append" segments deep.
        $expectedDeepestKey = 'value.' . str_repeat('append.', 17);
        $this->assertStringContainsString(rtrim($expectedDeepestKey, '.') . ' = TEXT', $wrap);
        // and no 18-deep key exists
        $this->assertStringNotContainsString(
            'value.' . str_repeat('append.', 18) . ' = TEXT',
            $wrap
        );
    }

    public function testWrapEndsWithFinalDdWrap()
    {
        $wrap = (new AddPreprintSourceCitationRowUpdate())->buildWrap();

        $this->assertStringEndsWith('value.wrap3 = <dd>|</dd>', $wrap);
    }
}
