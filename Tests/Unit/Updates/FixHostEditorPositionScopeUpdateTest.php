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

use EWW\Dpf\Updates\FixHostEditorPositionScopeUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class FixHostEditorPositionScopeUpdateTest extends UnitTestCase
{
    public function testFixesFirstEditorPositionScope()
    {
        $wizard = new FixHostEditorPositionScopeUpdate();

        $fix = $wizard->computeFix([
            'index_name' => 'original_publisher',
            'xpath' => './mods:relatedItem[@type="host"]/mods:name[mods:role/mods:roleTerm="edt"][1]/mods:displayForm',
        ]);

        $this->assertSame(
            '(.//mods:relatedItem[@type="host"]/mods:name[mods:role/mods:roleTerm="edt"])[1]/mods:displayForm',
            $fix['xpath']
        );
    }

    public function testFixesSecondEditorPositionScope()
    {
        $wizard = new FixHostEditorPositionScopeUpdate();

        $fix = $wizard->computeFix([
            'index_name' => 'original_publisher_1',
            'xpath' => './mods:relatedItem[@type="host"]/mods:name[mods:role/mods:roleTerm="edt"][2]/mods:displayForm',
        ]);

        $this->assertSame(
            '(.//mods:relatedItem[@type="host"]/mods:name[mods:role/mods:roleTerm="edt"])[2]/mods:displayForm',
            $fix['xpath']
        );
    }

    public function testFixesThirdEditorPositionScope()
    {
        $wizard = new FixHostEditorPositionScopeUpdate();

        $fix = $wizard->computeFix([
            'index_name' => 'original_publisher_2',
            'xpath' => './mods:relatedItem[@type="host"]/mods:name[mods:role/mods:roleTerm="edt"][3]/mods:displayForm',
        ]);

        $this->assertSame(
            '(.//mods:relatedItem[@type="host"]/mods:name[mods:role/mods:roleTerm="edt"])[3]/mods:displayForm',
            $fix['xpath']
        );
    }

    public function testAlreadyFixedRowsProduceNoChanges()
    {
        $wizard = new FixHostEditorPositionScopeUpdate();

        $fix = $wizard->computeFix([
            'index_name' => 'original_publisher',
            'xpath' => '(.//mods:relatedItem[@type="host"]/mods:name[mods:role/mods:roleTerm="edt"])[1]/mods:displayForm',
        ]);

        $this->assertSame([], $fix);
    }

    public function testUnrelatedRowsProduceNoChanges()
    {
        $wizard = new FixHostEditorPositionScopeUpdate();

        $fix = $wizard->computeFix([
            'index_name' => 'title',
            'xpath' => './mods:titleInfo/mods:title',
        ]);

        $this->assertSame([], $fix);
    }
}
