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

use EWW\Dpf\Updates\AddSourceCitationPersonInstitutionRolesUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class AddSourceCitationPersonInstitutionRolesUpdateTest extends UnitTestCase
{
    public function testDefinesEightNewRows()
    {
        $rows = (new AddSourceCitationPersonInstitutionRolesUpdate())->getNewRows();

        $this->assertCount(8, $rows);
        $this->assertSame(
            [
                'original_author', 'original_author_1',
                'original_translator', 'original_translator_1',
                'original_institution_author', 'original_institution_author_1',
                'original_institution_publisher', 'original_institution_publisher_1',
            ],
            array_column($rows, 'index_name')
        );
    }

    public function testAuthorRowsScopePersonalAutRoleAndPosition()
    {
        $rows = (new AddSourceCitationPersonInstitutionRolesUpdate())->getNewRows();
        $byName = array_column($rows, null, 'index_name');

        $this->assertSame(
            '(.//mods:relatedItem[@type="host"]/mods:name[@type="personal"][mods:role/mods:roleTerm="aut"])[1]/mods:displayForm',
            $byName['original_author']['xpath']
        );
        $this->assertSame(
            '(.//mods:relatedItem[@type="host"]/mods:name[@type="personal"][mods:role/mods:roleTerm="aut"])[2]/mods:displayForm',
            $byName['original_author_1']['xpath']
        );
    }

    public function testInstitutionRowsScopeCorporateType()
    {
        $rows = (new AddSourceCitationPersonInstitutionRolesUpdate())->getNewRows();
        $byName = array_column($rows, null, 'index_name');

        $this->assertStringContainsString(
            'mods:name[@type="corporate"][mods:role/mods:roleTerm="aut"]',
            $byName['original_institution_author']['xpath']
        );
        $this->assertStringContainsString(
            'mods:name[@type="corporate"][mods:role/mods:roleTerm="edt"]',
            $byName['original_institution_publisher']['xpath']
        );
    }

    public function testTranslatorRowsScopeTrlRole()
    {
        $rows = (new AddSourceCitationPersonInstitutionRolesUpdate())->getNewRows();
        $byName = array_column($rows, null, 'index_name');

        $this->assertStringContainsString('roleTerm="trl"', $byName['original_translator']['xpath']);
    }

    public function testSortingValuesAreDistinct()
    {
        $rows = (new AddSourceCitationPersonInstitutionRolesUpdate())->getNewRows();
        $sortings = array_column($rows, 'sorting');

        $this->assertSame($sortings, array_unique($sortings));
    }
}
