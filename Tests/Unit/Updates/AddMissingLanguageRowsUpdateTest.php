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

use EWW\Dpf\Updates\AddMissingLanguageRowsUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddMissingLanguageRowsUpdateTest extends UnitTestCase
{
    public function testDefinesFourNewLanguageRows()
    {
        $rows = (new AddMissingLanguageRowsUpdate())->getNewRows();

        $this->assertCount(4, $rows);
        $this->assertSame(
            ['classification_geo', 'classification_mkd', 'classification_swe', 'abstract_cat'],
            array_column($rows, 'index_name')
        );
    }

    public function testClassificationRowsUseCorrectLangAttribute()
    {
        $rows = (new AddMissingLanguageRowsUpdate())->getNewRows();

        $this->assertSame('./mods:classification[@authority="z"][@lang="geo"]', $rows[0]['xpath']);
        $this->assertSame('./mods:classification[@authority="z"][@lang="mkd"]', $rows[1]['xpath']);
        $this->assertSame('./mods:classification[@authority="z"][@lang="swe"]', $rows[2]['xpath']);
    }

    public function testAbstractRowUsesCorrectLangAttribute()
    {
        $rows = (new AddMissingLanguageRowsUpdate())->getNewRows();

        $this->assertSame('./mods:abstract[@type="summary"][@lang="cat"]', $rows[3]['xpath']);
    }

    public function testSortingValuesAreDistinctAndDoNotCollideWithExistingRows()
    {
        $rows = (new AddMissingLanguageRowsUpdate())->getNewRows();
        $sortings = array_column($rows, 'sorting');

        $this->assertSame($sortings, array_unique($sortings));
        // classification neighbors: classification_ger=81152, classification_ita=81408
        $this->assertGreaterThan(81152, $sortings[0]);
        $this->assertLessThan(81408, $sortings[2]);
        // abstract neighbors: abstract_ger=46336, abstract_ita=46592
        $this->assertGreaterThan(46336, $sortings[3]);
        $this->assertLessThan(46592, $sortings[3]);
    }
}
