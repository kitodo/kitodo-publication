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

use EWW\Dpf\Updates\AddOtherVersionIdentifierRowsUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddOtherVersionIdentifierRowsUpdateTest extends UnitTestCase
{
    public function testGetIdentifier()
    {
        $this->assertSame(
            'dpfAddOtherVersionIdentifierRows',
            (new AddOtherVersionIdentifierRowsUpdate())->getIdentifier()
        );
    }

    public function testDefinesTwoNewRows()
    {
        $rows = (new AddOtherVersionIdentifierRowsUpdate())->getNewRows();

        $this->assertCount(2, $rows);
    }

    /**
     * A first attempt reused two rows that turned out to be l18n overlays
     * (sys_language_uid=1). These rows must rely on the table's own default
     * (0) for sys_language_uid/l18n_parent, not set it explicitly wrong -
     * asserting the keys are absent here means "use the column default",
     * which is 0 for both (verified against the live schema).
     */
    public function testRowsDoNotOverrideLanguageFields()
    {
        $rows = (new AddOtherVersionIdentifierRowsUpdate())->getNewRows();

        foreach ($rows as $row) {
            $this->assertArrayNotHasKey('sys_language_uid', $row);
            $this->assertArrayNotHasKey('l18n_parent', $row);
            $this->assertArrayNotHasKey('hidden', $row);
        }
    }

    public function testXpathsAreUnscopedByPosition()
    {
        $rows = (new AddOtherVersionIdentifierRowsUpdate())->getNewRows();
        $byName = array_column($rows, null, 'index_name');

        $this->assertSame(
            './mods:relatedItem[@type="otherversion"]/mods:identifier[@type="doi"]',
            $byName['otherversion_doi']['xpath']
        );
        $this->assertSame(
            './mods:relatedItem[@type="otherversion"]/mods:location/mods:url',
            $byName['otherversion_url']['xpath']
        );
        foreach ($rows as $row) {
            $this->assertStringNotContainsString('[1]', $row['xpath']);
        }
    }

    public function testLabelsMatchTicketTerminology()
    {
        $rows = (new AddOtherVersionIdentifierRowsUpdate())->getNewRows();
        $byName = array_column($rows, null, 'index_name');

        $this->assertSame('DOI', $byName['otherversion_doi']['label']);
        $this->assertSame('Link', $byName['otherversion_url']['label']);
    }

    public function testSortingPlacesRowsInsideIdentifierGroup()
    {
        $rows = (new AddOtherVersionIdentifierRowsUpdate())->getNewRows();

        foreach ($rows as $row) {
            // identifier_pmid (46304) precedes, abstract_ger (46336) follows.
            $this->assertGreaterThan(46304, $row['sorting']);
            $this->assertLessThan(46336, $row['sorting']);
        }
    }

    public function testSortingValuesAreDistinct()
    {
        $rows = (new AddOtherVersionIdentifierRowsUpdate())->getNewRows();
        $sortings = array_column($rows, 'sorting');

        $this->assertSame($sortings, array_unique($sortings));
    }
}
