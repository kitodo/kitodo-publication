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

use EWW\Dpf\Updates\FixIssnOtherVersionScopeUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class FixIssnOtherVersionScopeUpdateTest extends UnitTestCase
{
    public function testWidensIssnXpath()
    {
        $wizard = new FixIssnOtherVersionScopeUpdate();

        $fix = $wizard->computeFix([
            'uid' => 255,
            'index_name' => 'issn',
            'xpath' => './mods:identifier[@type="issn"]',
        ]);

        $this->assertSame(
            './mods:identifier[@type="issn"]'
                . ' | ./mods:relatedItem[@type="otherversion"]/mods:identifier[@type="issn"]',
            $fix['xpath']
        );
    }

    public function testAlreadyFixedRowNeedsNoFix()
    {
        $wizard = new FixIssnOtherVersionScopeUpdate();

        $fix = $wizard->computeFix([
            'uid' => 255,
            'index_name' => 'issn',
            'xpath' => './mods:identifier[@type="issn"]'
                . ' | ./mods:relatedItem[@type="otherversion"]/mods:identifier[@type="issn"]',
        ]);

        $this->assertSame([], $fix);
    }

    public function testUnrelatedRowNeedsNoFix()
    {
        $wizard = new FixIssnOtherVersionScopeUpdate();

        $fix = $wizard->computeFix([
            'uid' => 270,
            'index_name' => 'ISSN0',
            'xpath' => '',
        ]);

        $this->assertSame([], $fix);
    }

    public function testCustomizedXpathNeedsNoFix()
    {
        $wizard = new FixIssnOtherVersionScopeUpdate();

        $fix = $wizard->computeFix([
            'uid' => 255,
            'index_name' => 'issn',
            'xpath' => './mods:identifier[@type="eissn"]',
        ]);

        $this->assertSame([], $fix);
    }
}
