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

use EWW\Dpf\Updates\AddRelationDetailFieldsUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddRelationDetailFieldsUpdateTest extends UnitTestCase
{
    public function testDefinesFifteenNewRows()
    {
        $rows = (new AddRelationDetailFieldsUpdate())->getNewRows();

        $this->assertCount(15, $rows);
    }

    public function testSeriesRowsScopeSeriesRelatedItem()
    {
        $rows = (new AddRelationDetailFieldsUpdate())->getNewRows();
        $byName = array_column($rows, null, 'index_name');

        $this->assertSame(
            './mods:relatedItem[@type="series"]/mods:identifier[@type="doi"]',
            $byName['series_doi']['xpath']
        );
        $this->assertSame(
            './mods:relatedItem[@type="series"]/mods:note',
            $byName['series_note']['xpath']
        );
    }

    public function testMultivolumeRowsScopeHostRelatedItem()
    {
        $rows = (new AddRelationDetailFieldsUpdate())->getNewRows();
        $byName = array_column($rows, null, 'index_name');

        $this->assertSame(
            './mods:relatedItem[@type="host"]/mods:identifier[@type="zdb"]',
            $byName['multivolume_zdb']['xpath']
        );
        $this->assertSame(
            './mods:relatedItem[@type="host"]/mods:part[@type="volume"]/mods:detail/mods:number',
            $byName['multivolume_volume']['xpath']
        );
    }

    public function testNoPrecedingOrSucceedingRowsAdded()
    {
        $rows = (new AddRelationDetailFieldsUpdate())->getNewRows();

        foreach ($rows as $row) {
            $this->assertStringNotContainsString('preceding', $row['index_name']);
            $this->assertStringNotContainsString('succeeding', $row['index_name']);
        }
    }

    public function testSortingValuesAreDistinct()
    {
        $rows = (new AddRelationDetailFieldsUpdate())->getNewRows();
        $sortings = array_column($rows, 'sorting');

        $this->assertSame($sortings, array_unique($sortings));
    }
}
