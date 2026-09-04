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

use EWW\Dpf\Updates\AddMissingIdentifierRowsUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddMissingIdentifierRowsUpdateTest extends UnitTestCase
{
    public function testDefinesSevenNewIdentifierRows()
    {
        $rows = (new AddMissingIdentifierRowsUpdate())->getNewRows();

        $this->assertCount(7, $rows);
        $this->assertSame(
            ['identifier_urn', 'identifier_handle', 'identifier_uri', 'identifier_ismn',
                'identifier_other', 'identifier_isi', 'identifier_pmid'],
            array_column($rows, 'index_name')
        );
    }

    public function testNewRowsUseWidenedXpathScope()
    {
        foreach ((new AddMissingIdentifierRowsUpdate())->getNewRows() as $row) {
            $this->assertStringContainsString('| ./mods:relatedItem[@type="otherversion"]/mods:identifier', $row['xpath']);
        }
    }

    public function testSortingValuesAreDistinctAndDoNotCollideWithExistingRows()
    {
        $rows = (new AddMissingIdentifierRowsUpdate())->getNewRows();
        $sortings = array_column($rows, 'sorting');

        $this->assertSame($sortings, array_unique($sortings));
        // Neighbors: doi=46080, Abstract (DE)=46336
        $this->assertGreaterThan(46080, min($sortings));
        $this->assertLessThan(46336, max($sortings));
    }

    public function testWidensDoiXpath()
    {
        $wizard = new AddMissingIdentifierRowsUpdate();

        $fix = $wizard->computeFix([
            'uid' => 253,
            'index_name' => 'doi',
            'xpath' => './mods:identifier[@type="doi"]',
        ]);

        $this->assertSame(
            './mods:identifier[@type="doi"]'
                . ' | ./mods:relatedItem[@type="otherversion"]/mods:identifier[@type="doi"]',
            $fix['xpath']
        );
    }

    public function testWidensZdbXpath()
    {
        $wizard = new AddMissingIdentifierRowsUpdate();

        $fix = $wizard->computeFix([
            'uid' => 273,
            'index_name' => 'zdb',
            'xpath' => './mods:identifier[@type="zdb"]',
        ]);

        $this->assertSame(
            './mods:identifier[@type="zdb"]'
                . ' | ./mods:relatedItem[@type="otherversion"]/mods:identifier[@type="zdb"]',
            $fix['xpath']
        );
    }

    public function testAlreadyWidenedRowNeedsNoFix()
    {
        $wizard = new AddMissingIdentifierRowsUpdate();

        $fix = $wizard->computeFix([
            'uid' => 253,
            'index_name' => 'doi',
            'xpath' => './mods:identifier[@type="doi"]'
                . ' | ./mods:relatedItem[@type="otherversion"]/mods:identifier[@type="doi"]',
        ]);

        $this->assertSame([], $fix);
    }

    public function testUnrelatedRowNeedsNoFix()
    {
        $wizard = new AddMissingIdentifierRowsUpdate();

        $fix = $wizard->computeFix([
            'uid' => 272,
            'index_name' => 'isbn0',
            'xpath' => './mods:identifier[@type="isbn"]',
        ]);

        $this->assertSame([], $fix);
    }
}
