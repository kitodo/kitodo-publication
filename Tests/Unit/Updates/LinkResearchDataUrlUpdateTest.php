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

use EWW\Dpf\Updates\LinkResearchDataUrlUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class LinkResearchDataUrlUpdateTest extends UnitTestCase
{
    private const PLAIN_WRAP = "key.wrap = <dt>|</dt>\r\nvalue.required = 1\r\nvalue.wrap = <dd>|</dd>";

    public function testAddsTypolinkOnResearchDataUrl1()
    {
        $wizard = new LinkResearchDataUrlUpdate();

        $fix = $wizard->computeFix([
            'uid' => 151,
            'index_name' => 'researchData_url1',
            'wrap' => self::PLAIN_WRAP,
        ]);

        $this->assertStringContainsString('value.dataWrap = {field:researchData_url1}', $fix['wrap']);
        $this->assertStringContainsString(
            'value.dataWrap.typolink.parameter = {field:researchData_url1}',
            $fix['wrap']
        );
        $this->assertStringContainsString(
            'value.dataWrap.typolink.parameter.fieldRequired = researchData_url1',
            $fix['wrap']
        );
        $this->assertStringContainsString('value.wrap = <dd>|</dd>', $fix['wrap']);
    }

    public function testAddsTypolinkOnResearchDataUrl4()
    {
        $wizard = new LinkResearchDataUrlUpdate();

        $fix = $wizard->computeFix([
            'uid' => 148,
            'index_name' => 'researchData_url4',
            'wrap' => self::PLAIN_WRAP,
        ]);

        $this->assertStringContainsString(
            'value.dataWrap.typolink.parameter = {field:researchData_url4}',
            $fix['wrap']
        );
    }

    public function testAlreadyFixedRowNeedsNoFix()
    {
        $wizard = new LinkResearchDataUrlUpdate();

        $fix = $wizard->computeFix([
            'uid' => 151,
            'index_name' => 'researchData_url1',
            'wrap' => "value.dataWrap = {field:researchData_url1}\r\n"
                . "value.dataWrap.typolink.parameter = {field:researchData_url1}\r\n"
                . "value.dataWrap.typolink.parameter.fieldRequired = researchData_url1\r\n"
                . self::PLAIN_WRAP,
        ]);

        $this->assertSame([], $fix);
    }

    public function testUnrelatedRowNeedsNoFix()
    {
        $wizard = new LinkResearchDataUrlUpdate();

        $fix = $wizard->computeFix([
            'uid' => 127,
            'index_name' => 'researchData1',
            'wrap' => self::PLAIN_WRAP,
        ]);

        $this->assertSame([], $fix);
    }

    public function testAffectedRowWithoutOldValueWrapNeedsNoFix()
    {
        $wizard = new LinkResearchDataUrlUpdate();

        $fix = $wizard->computeFix([
            'uid' => 151,
            'index_name' => 'researchData_url1',
            'wrap' => 'key.wrap = <dt>|</dt>',
        ]);

        $this->assertSame([], $fix);
    }
}
