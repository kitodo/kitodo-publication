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

use EWW\Dpf\Updates\AddInstitutionAuthorContributorRolesUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddInstitutionAuthorContributorRolesUpdateTest extends UnitTestCase
{
    public function testDefinesTwoRoleRows()
    {
        $rows = (new AddInstitutionAuthorContributorRolesUpdate())->getNewRows();

        $this->assertCount(2, $rows);
        $this->assertSame('corporation_author', $rows[0]['index_name']);
        $this->assertSame('corporation_contributor', $rows[1]['index_name']);
    }

    public function testXpathMirrorsExistingEditorOtherPatternWithCorrectRoleTerm()
    {
        $rows = (new AddInstitutionAuthorContributorRolesUpdate())->getNewRows();

        $this->assertSame(
            'mods:name[@type="corporate"][mods:role/mods:roleTerm="aut"]/mods:namePart',
            $rows[0]['xpath']
        );
        $this->assertSame(
            'mods:name[@type="corporate"][mods:role/mods:roleTerm="ctb"]/mods:namePart',
            $rows[1]['xpath']
        );
    }

    public function testRowsAreNotHiddenAndUseStandardWrap()
    {
        foreach ((new AddInstitutionAuthorContributorRolesUpdate())->getNewRows() as $row) {
            $this->assertArrayNotHasKey('hidden', $row);
            $this->assertSame(1, $row['format']);
            $this->assertSame('MODS', $row['format_type']);
            $this->assertStringContainsString('key.wrap = <dt>|</dt>', $row['wrap']);
            $this->assertStringContainsString('value.wrap = <dd>|</dd>', $row['wrap']);
        }
    }

    public function testSortingValuesAreDistinctAndDoNotCollideWithExistingRows()
    {
        $rows = (new AddInstitutionAuthorContributorRolesUpdate())->getNewRows();
        $sortings = array_column($rows, 'sorting');

        $this->assertSame($sortings, array_unique($sortings));
        // Neighbors: publisher=12800/corporation_editor=13056, corporation_other=114688/version=114944
        $this->assertGreaterThan(12800, $sortings[0]);
        $this->assertLessThan(13056, $sortings[0]);
        $this->assertGreaterThan(114688, $sortings[1]);
        $this->assertLessThan(114944, $sortings[1]);
    }

    public function testRowsDoNotCollideWithExistingIndexNames()
    {
        $existing = ['corporation_editor', 'corporation_other', 'author1', 'publisher'];

        foreach ((new AddInstitutionAuthorContributorRolesUpdate())->getNewRows() as $row) {
            $this->assertNotContains($row['index_name'], $existing);
        }
    }
}
