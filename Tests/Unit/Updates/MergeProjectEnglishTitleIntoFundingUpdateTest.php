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

use EWW\Dpf\Updates\MergeProjectEnglishTitleIntoFundingUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class MergeProjectEnglishTitleIntoFundingUpdateTest extends UnitTestCase
{
    public function testGetIdentifier()
    {
        $this->assertSame(
            'dpfMergeProjectEnglishTitleIntoFunding',
            (new MergeProjectEnglishTitleIntoFundingUpdate())->getIdentifier()
        );
    }

    /**
     * Reproduces the real DB row's confirmed quirks: CRLF line endings
     * throughout (tx_dpf_metadata.wrap requires \r\n, plain \n silently
     * no-ops - see feedback_dpf_metadata_wrap_crlf) and mixed indentation
     * (slot 1 uses tabs, slot 2 uses 4 spaces) between the two project_N
     * blocks in the real composite wrap.
     */
    private function twoSlotFixture(): string
    {
        return "key.wrap = <dt>|</dt>\r\n\r\nvalue.append = TEXT\r\nvalue.append {\r\n"
            . "\tvalue = {field:project_1}\r\n"
            . "\tvalue.insertData = 1\r\n"
            . "\tvalue.noTrimWrap = |||<br />\r\n"
            . "\tvalue.noTrimWrap.fieldRequired = project_1\r\n"
            . "}\r\n"
            . "value.append.append = TEXT\r\n"
            . "value.append.append {\r\n"
            . "    value = {field:project_2}\r\n"
            . "    value.insertData = 1\r\n"
            . "    value.noTrimWrap = |||<br />\r\n"
            . "    value.noTrimWrap.fieldRequired = project_2\r\n"
            . "}\r\n";
    }

    public function testMergeReplacesValueLinePairWithCObjectBlock()
    {
        $result = (new MergeProjectEnglishTitleIntoFundingUpdate())->mergeEnglishTitles($this->twoSlotFixture());

        $this->assertStringContainsString('value.cObject = COA', $result);
        $this->assertStringContainsString('10.value = {field:project_1}', $result);
        $this->assertStringContainsString('value = {field:project_1_en}', $result);
        $this->assertStringContainsString('10.value = {field:project_2}', $result);
        $this->assertStringContainsString('value = {field:project_2_en}', $result);
    }

    public function testMergeLeavesNoTrimWrapUntouched()
    {
        $result = (new MergeProjectEnglishTitleIntoFundingUpdate())->mergeEnglishTitles($this->twoSlotFixture());

        $this->assertStringContainsString('value.noTrimWrap = |||<br />', $result);
        $this->assertStringContainsString('value.noTrimWrap.fieldRequired = project_1', $result);
        $this->assertStringContainsString('value.noTrimWrap.fieldRequired = project_2', $result);
        $this->assertSame(2, substr_count($result, 'noTrimWrap.fieldRequired'));
    }

    public function testMergeIsCrlfAware()
    {
        $result = (new MergeProjectEnglishTitleIntoFundingUpdate())->mergeEnglishTitles($this->twoSlotFixture());

        // Every inserted line must use CRLF, matching the rest of the row -
        // a plain \n insertion would silently no-op per the documented gotcha.
        $this->assertStringContainsString("value.cObject = COA\r\n", $result);
        $this->assertStringNotContainsString("COA\n\t10", $result, 'inserted lines must be CRLF, not bare LF');
    }

    public function testMergeNoOpsWhenAnchorMissing()
    {
        $update = new MergeProjectEnglishTitleIntoFundingUpdate();
        $unrelated = "key.wrap = <dt>|</dt>\r\nvalue = something else\r\n";

        $this->assertSame($unrelated, $update->mergeEnglishTitles($unrelated));
    }

    public function testMergeSkipsSlotWhenStructureDoesNotMatchExpectedLinePair()
    {
        $update = new MergeProjectEnglishTitleIntoFundingUpdate();
        $unexpected = "value = {field:project_1}\r\nsomething.else = 1\r\n";

        $result = $update->mergeEnglishTitles($unexpected);

        $this->assertSame($unexpected, $result, 'must not guess when the following line is not value.insertData = 1');
    }
}
