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

use EWW\Dpf\Updates\FixIsbnOtherVersionScopeUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class FixIsbnOtherVersionScopeUpdateTest extends UnitTestCase
{
    public function testWidensIsbn0Xpath()
    {
        $wizard = new FixIsbnOtherVersionScopeUpdate();

        $fix = $wizard->computeFix([
            'uid' => 272,
            'index_name' => 'isbn0',
            'xpath' => './mods:identifier[@type="isbn"]',
        ]);

        $this->assertSame(
            './mods:identifier[@type="isbn"]'
                . ' | ./mods:relatedItem[@type="otherversion"]/mods:identifier[@type="isbn"]',
            $fix['xpath']
        );
    }

    public function testAlreadyFixedRowNeedsNoFix()
    {
        $wizard = new FixIsbnOtherVersionScopeUpdate();

        $fix = $wizard->computeFix([
            'uid' => 272,
            'index_name' => 'isbn0',
            'xpath' => './mods:identifier[@type="isbn"]'
                . ' | ./mods:relatedItem[@type="otherversion"]/mods:identifier[@type="isbn"]',
        ]);

        $this->assertSame([], $fix);
    }

    public function testUnrelatedRowNeedsNoFix()
    {
        $wizard = new FixIsbnOtherVersionScopeUpdate();

        $fix = $wizard->computeFix([
            'uid' => 300,
            'index_name' => 'original_place',
            'xpath' => './mods:identifier[@type="isbn"]',
        ]);

        $this->assertSame([], $fix);
    }

    public function testCustomizedXpathNeedsNoFix()
    {
        $wizard = new FixIsbnOtherVersionScopeUpdate();

        $fix = $wizard->computeFix([
            'uid' => 272,
            'index_name' => 'isbn0',
            'xpath' => './mods:identifier[@type="isbn13"]',
        ]);

        $this->assertSame([], $fix);
    }
}
