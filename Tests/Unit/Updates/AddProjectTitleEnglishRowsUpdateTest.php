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

use EWW\Dpf\Updates\AddProjectTitleEnglishRowsUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddProjectTitleEnglishRowsUpdateTest extends UnitTestCase
{
    public function testDefinesTenNewRows()
    {
        $rows = (new AddProjectTitleEnglishRowsUpdate())->getNewRows();

        $this->assertCount(10, $rows);
        $this->assertSame(
            ['project_1_en', 'project_2_en', 'project_3_en', 'project_4_en', 'project_5_en',
                'project_6_en', 'project_7_en', 'project_8_en', 'project_9_en', 'project_10_en'],
            array_column($rows, 'index_name')
        );
    }

    public function testRowXpathsUseNameEnAttribute()
    {
        $rows = (new AddProjectTitleEnglishRowsUpdate())->getNewRows();

        $this->assertSame(
            './mods:extension[slub:info/slub:project][1]/slub:info/slub:project/@nameEn',
            $rows[0]['xpath']
        );
        $this->assertSame(
            './mods:extension[slub:info/slub:project][10]/slub:info/slub:project/@nameEn',
            $rows[9]['xpath']
        );
    }

    public function testSortingValuesAreDistinct()
    {
        $rows = (new AddProjectTitleEnglishRowsUpdate())->getNewRows();
        $sortings = array_column($rows, 'sorting');

        $this->assertSame($sortings, array_unique($sortings));
    }

    public function testSortingPlacesEnglishRowImmediatelyAfterItsGermanCounterpart()
    {
        $rows = (new AddProjectTitleEnglishRowsUpdate())->getNewRows();
        $byName = array_column($rows, null, 'index_name');

        // project_1's live sorting is 111360 - project_1_en must sit right after it.
        $this->assertSame(111361, $byName['project_1_en']['sorting']);
    }
}
