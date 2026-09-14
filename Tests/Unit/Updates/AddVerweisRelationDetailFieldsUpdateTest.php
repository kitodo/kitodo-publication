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

use EWW\Dpf\Updates\AddVerweisRelationDetailFieldsUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddVerweisRelationDetailFieldsUpdateTest extends UnitTestCase
{
    public function testGetIdentifier()
    {
        $this->assertSame(
            'dpfAddVerweisRelationDetailFields',
            (new AddVerweisRelationDetailFieldsUpdate())->getIdentifier()
        );
    }

    public function testDefinesSeventyNewRows()
    {
        $rows = (new AddVerweisRelationDetailFieldsUpdate())->getNewRows();

        $this->assertCount(70, $rows);
    }

    public function testNewRowsAreHiddenAndScopedToTheirSlot()
    {
        $rows = (new AddVerweisRelationDetailFieldsUpdate())->getNewRows();
        $byName = array_column($rows, null, 'index_name');

        $this->assertSame(1, $byName['references_urn_1']['hidden']);
        $this->assertSame(
            './mods:relatedItem[@type="references" and not(mods:typeOfResource)][1]/mods:identifier[@type="urn"]',
            $byName['references_urn_1']['xpath']
        );
        $this->assertSame(
            './mods:relatedItem[@type="references" and not(mods:typeOfResource)][10]/mods:part[@type="volume"]/mods:detail/mods:number',
            $byName['references_volume_10']['xpath']
        );
        $this->assertSame(
            './mods:relatedItem[@type="references" and not(mods:typeOfResource)][3]/mods:part[@type="issue"]/mods:detail/mods:number',
            $byName['references_issue_3']['xpath']
        );
    }

    public function testSortingValuesAreDistinct()
    {
        $rows = (new AddVerweisRelationDetailFieldsUpdate())->getNewRows();
        $sortings = array_column($rows, 'sorting');

        $this->assertSame($sortings, array_unique($sortings));
    }

    /**
     * Minimal 2-slot fixture reproducing the real DB row's confirmed quirks:
     * a leading broken remnant line (`50. - TEXT` / `50.field = ISBN`) and
     * content sub-field order that differs between slot 1 (DOI before URL)
     * and slot 2 (URL before DOI) - the real reason extendWrap() anchors on
     * the closing `wrap = <dd>|</dd>` line rather than a specific field name.
     */
    private function twoSlotFixture(): string
    {
        return "key.wrap = <dt>|</dt>\r\n\r\nvalue.append = COA\r\nvalue.append {\r\n"
            . "1 = COA\r\n1.if {\r\n\tisTrue.cObject = COA\r\n\tisTrue.cObject {\r\n"
            . "\t\t10 = TEXT\r\n\t\t10.field = references1\r\n"
            . "\t\t20 = TEXT\r\n\t\t20.field = note_references1\r\n"
            . "\t\t30 = TEXT\r\n\t\t30.field = references_doi_1\r\n"
            . "\t\t40 = TEXT\r\n\t\t40.field = url_references1\r\n"
            . "\t\t50. - TEXT\r\n\t\t50.field = ISBN\r\n"
            . "\t}\r\n}\r\n"
            . "1 {\r\n\t10 = TEXT\r\n\t10 {\r\n\t\tvalue.dataWrap = {field:references1}\r\n\t}\r\n"
            . "\t30 = TEXT\r\n\t30 {\r\n\t\tfield = references_doi_1\r\n\t\twrap = DOI:&nbsp;| <br />\r\n\t}\r\n"
            . "\t40 = TEXT\r\n\t40 {\r\n\t\tfield = url_references1\r\n\t\twrap = Link:&nbsp;| <br />\r\n\t}\r\n"
            . "\twrap = <dd>|</dd>\r\n}\r\n"
            . "2 = COA\r\n2.if {\r\n\tisTrue.cObject = COA\r\n\tisTrue.cObject {\r\n"
            . "\t\t10 = TEXT\r\n\t\t10.field = references2\r\n"
            . "\t\t20 = TEXT\r\n\t\t20.field = note_references2\r\n"
            . "\t\t30 = TEXT\r\n\t\t30.field = references_doi_2\r\n"
            . "\t\t40 = TEXT\r\n\t\t40.field = url_references2\r\n"
            . "\t\t50. - TEXT\r\n\t\t50.field = ISBN\r\n"
            . "\t}\r\n}\r\n"
            . "2 {\r\n\t10 = TEXT\r\n\t10 {\r\n\t\tvalue.dataWrap = {field:references2}\r\n\t}\r\n"
            . "\t30 = TEXT\r\n\t30 {\r\n\t\tfield = url_references2\r\n\t\twrap = Link:&nbsp;| <br />\r\n\t}\r\n"
            . "\t40 = TEXT\r\n\t40 {\r\n\t\tfield = references_doi_2\r\n\t\twrap = DOI:&nbsp;| <br />\r\n\t}\r\n"
            . "\twrap = <dd>|</dd>\r\n}\r\n"
            . "}\r\n";
    }

    public function testExtendWrapRemovesBrokenRemnantLine()
    {
        $result = (new AddVerweisRelationDetailFieldsUpdate())->extendWrap($this->twoSlotFixture());

        $this->assertStringNotContainsString('50. - TEXT', $result);
        $this->assertStringNotContainsString('50.field = ISBN', $result);
    }

    public function testExtendWrapAddsGateFieldsForBothSlots()
    {
        $result = (new AddVerweisRelationDetailFieldsUpdate())->extendWrap($this->twoSlotFixture());

        $this->assertStringContainsString('50.field = references_urn_1', $result);
        $this->assertStringContainsString('110.field = references_issue_1', $result);
        $this->assertStringContainsString('50.field = references_urn_2', $result);
        $this->assertStringContainsString('110.field = references_issue_2', $result);
    }

    public function testExtendWrapAddsContentFieldsRegardlessOfSlotFieldOrder()
    {
        $result = (new AddVerweisRelationDetailFieldsUpdate())->extendWrap($this->twoSlotFixture());

        // Slot 1 content ends DOI-then-URL, slot 2 ends URL-then-DOI - both
        // must get their new fields inserted right before their own closing
        // "wrap = <dd>|</dd>" line, not the other slot's.
        $this->assertSame(
            2,
            substr_count($result, 'wrap = <dd>|</dd>'),
            'closing marker must not be duplicated or consumed'
        );
        $this->assertStringContainsString('field = references_urn_1', $result);
        $this->assertStringContainsString('field = references_issue_1', $result);
        $this->assertStringContainsString('field = references_urn_2', $result);
        $this->assertStringContainsString('field = references_issue_2', $result);

        $slot1End = strpos($result, 'field = references_urn_1');
        $slot1Close = strpos($result, 'wrap = <dd>|</dd>');
        $slot2Start = strpos($result, '2 = COA');
        $this->assertLessThan($slot1Close, $slot1End, 'slot 1 new fields must land before slot 1s own closing tag');
        $this->assertGreaterThan($slot2Start, strpos($result, 'field = references_urn_2'), 'slot 2 new fields must land after slot 2 starts, not inside slot 1');
    }

    public function testExtendWrapLeavesExistingFieldsUntouched()
    {
        $result = (new AddVerweisRelationDetailFieldsUpdate())->extendWrap($this->twoSlotFixture());

        $this->assertStringContainsString('field = references_doi_1', $result);
        $this->assertStringContainsString('field = url_references1', $result);
        $this->assertStringContainsString('field = url_references2', $result);
        $this->assertStringContainsString('field = references_doi_2', $result);
    }

    public function testExtendWrapIsIdempotentViaUpdateNecessaryMarker()
    {
        $update = new AddVerweisRelationDetailFieldsUpdate();
        $once = $update->extendWrap($this->twoSlotFixture());

        // The wizard's own executeUpdate() skips a record whose wrap already
        // contains 'references_urn_1' - confirm extendWrap's own output
        // would trip that guard, i.e. re-running produces the marker.
        $this->assertStringContainsString('references_urn_1', $once);
    }
}
