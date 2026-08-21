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

use EWW\Dpf\Updates\FixResearchDataUrl1LinkTypoUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class FixResearchDataUrl1LinkTypoUpdateTest extends UnitTestCase
{
    private const ITEM1_BLOCK = "\t30 = TEXT\n\t30 {\n\t\tfield = researchData_url\n\t\trequired = 1\n"
        . "\t\ttypolink.parameter.field = researchData_url1\n\t\twrap = Link:&nbsp;|<br />\n\t}\n\twrap = <dd>|</dd>\n}\n2 = COA";

    private const ITEM2_BLOCK = "\t30 = TEXT\n\t30 {\n\t\tfield = researchData_url2\n\t\trequired = 1\n"
        . "\t\ttypolink.parameter.field = researchData_url2\n\t\twrap = Link:&nbsp;|<br />\n\t}";

    public function testFixesItem1FieldNameTypo()
    {
        $wizard = new FixResearchDataUrl1LinkTypoUpdate();

        $fix = $wizard->computeFix([
            'uid' => 127,
            'index_name' => 'researchData1',
            'wrap' => self::ITEM1_BLOCK . "\n" . self::ITEM2_BLOCK,
        ]);

        $this->assertStringContainsString('field = researchData_url1', $fix['wrap']);
        $this->assertStringNotContainsString(
            "field = researchData_url\n\t\trequired = 1\n\t\ttypolink.parameter.field = researchData_url1",
            $fix['wrap']
        );
    }

    public function testLeavesItem2FieldNameUntouched()
    {
        $wizard = new FixResearchDataUrl1LinkTypoUpdate();

        $fix = $wizard->computeFix([
            'uid' => 127,
            'index_name' => 'researchData1',
            'wrap' => self::ITEM1_BLOCK . "\n" . self::ITEM2_BLOCK,
        ]);

        $this->assertStringContainsString('field = researchData_url2', $fix['wrap']);
    }

    public function testAlreadyFixedRowNeedsNoFix()
    {
        $wizard = new FixResearchDataUrl1LinkTypoUpdate();

        $fix = $wizard->computeFix([
            'uid' => 127,
            'index_name' => 'researchData1',
            'wrap' => str_replace("field = researchData_url\n", "field = researchData_url1\n", self::ITEM1_BLOCK),
        ]);

        $this->assertSame([], $fix);
    }

    public function testUnrelatedRowNeedsNoFix()
    {
        $wizard = new FixResearchDataUrl1LinkTypoUpdate();

        $fix = $wizard->computeFix([
            'uid' => 151,
            'index_name' => 'researchData_url1',
            'wrap' => self::ITEM1_BLOCK,
        ]);

        $this->assertSame([], $fix);
    }
}
