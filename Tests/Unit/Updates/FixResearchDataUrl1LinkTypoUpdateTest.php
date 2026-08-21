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

/**
 * Fixtures use CRLF line endings, matching the live tx_dpf_metadata.wrap
 * column (confirmed via HEX() on the actual row) - LF-only fixtures would
 * pass while the real fix silently no-ops against the live \r\n content,
 * exactly the bug this wizard's first draft shipped with.
 */
class FixResearchDataUrl1LinkTypoUpdateTest extends UnitTestCase
{
    private const ITEM1_BLOCK = "\t30 = TEXT\r\n\t30 {\r\n\t\tfield = researchData_url\r\n\t\trequired = 1\r\n"
        . "\t\ttypolink.parameter.field = researchData_url1\r\n\t\twrap = Link:&nbsp;|<br />\r\n\t}\r\n\twrap = <dd>|</dd>\r\n}\r\n2 = COA";

    private const ITEM2_BLOCK = "\t30 = TEXT\r\n\t30 {\r\n\t\tfield = researchData_url2\r\n\t\trequired = 1\r\n"
        . "\t\ttypolink.parameter.field = researchData_url2\r\n\t\twrap = Link:&nbsp;|<br />\r\n\t}";

    public function testFixesItem1FieldNameTypo()
    {
        $wizard = new FixResearchDataUrl1LinkTypoUpdate();

        $fix = $wizard->computeFix([
            'uid' => 127,
            'index_name' => 'researchData1',
            'wrap' => self::ITEM1_BLOCK . "\r\n" . self::ITEM2_BLOCK,
        ]);

        $this->assertStringContainsString('field = researchData_url1', $fix['wrap']);
        $this->assertStringNotContainsString(
            "field = researchData_url\r\n\t\trequired = 1\r\n\t\ttypolink.parameter.field = researchData_url1",
            $fix['wrap']
        );
    }

    public function testLeavesItem2FieldNameUntouched()
    {
        $wizard = new FixResearchDataUrl1LinkTypoUpdate();

        $fix = $wizard->computeFix([
            'uid' => 127,
            'index_name' => 'researchData1',
            'wrap' => self::ITEM1_BLOCK . "\r\n" . self::ITEM2_BLOCK,
        ]);

        $this->assertStringContainsString('field = researchData_url2', $fix['wrap']);
    }

    public function testAlreadyFixedRowNeedsNoFix()
    {
        $wizard = new FixResearchDataUrl1LinkTypoUpdate();

        $fix = $wizard->computeFix([
            'uid' => 127,
            'index_name' => 'researchData1',
            'wrap' => str_replace(
                "field = researchData_url\r\n",
                "field = researchData_url1\r\n",
                self::ITEM1_BLOCK
            ),
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

    public function testLfOnlyWrapNeedsNoFalsePositiveButRealCrlfWrapIsMatched()
    {
        $wizard = new FixResearchDataUrl1LinkTypoUpdate();

        // Guards against reintroducing an \n-only OLD_FIELD constant that
        // silently never matches the real CRLF-stored content.
        $lfOnly = str_replace("\r\n", "\n", self::ITEM1_BLOCK);
        $this->assertSame([], $wizard->computeFix([
            'uid' => 127,
            'index_name' => 'researchData1',
            'wrap' => $lfOnly,
        ]));

        $crlf = $wizard->computeFix([
            'uid' => 127,
            'index_name' => 'researchData1',
            'wrap' => self::ITEM1_BLOCK,
        ]);
        $this->assertNotSame([], $crlf);
    }
}
