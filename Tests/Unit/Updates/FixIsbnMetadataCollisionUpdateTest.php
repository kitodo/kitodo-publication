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

use EWW\Dpf\Updates\FixIsbnMetadataCollisionUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class FixIsbnMetadataCollisionUpdateTest extends UnitTestCase
{
    public function testDetectsDeadIsbnOverlayRow()
    {
        $wizard = new FixIsbnMetadataCollisionUpdate();

        $this->assertTrue($wizard->isIsbnCollisionRow([
            'l18n_parent' => 272,
            'index_name' => 'ISBN',
            'format' => 0,
            'xpath' => '',
        ]));
    }

    public function testDetectsCorruptedEisbnOverlayRow()
    {
        $wizard = new FixIsbnMetadataCollisionUpdate();

        $this->assertTrue($wizard->isIsbnCollisionRow([
            'l18n_parent' => 519,
            'index_name' => 'eisbn',
            'format' => 1,
            'xpath' => './mods:identifier[@type="isbn"]',
        ]));
    }

    public function testIgnoresWorkingParentRows()
    {
        $wizard = new FixIsbnMetadataCollisionUpdate();

        $this->assertFalse($wizard->isIsbnCollisionRow([
            'l18n_parent' => 0,
            'index_name' => 'isbn0',
            'format' => 1,
            'xpath' => './mods:identifier[@type="isbn"]',
        ]));
        $this->assertFalse($wizard->isIsbnCollisionRow([
            'l18n_parent' => 0,
            'index_name' => 'eisbn',
            'format' => 1,
            'xpath' => './mods:identifier[@type="eisbn"]',
        ]));
    }

    public function testIgnoresUnrelatedLegitimateL18nOverlayPairs()
    {
        $wizard = new FixIsbnMetadataCollisionUpdate();

        // e.g. urn_z DE/EN pair — same xpath, translated label only.
        $this->assertFalse($wizard->isIsbnCollisionRow([
            'l18n_parent' => 352,
            'index_name' => 'urn_z',
            'format' => 1,
            'xpath' => './mods:identifier[@type="qucosa:urn"]',
        ]));
    }
}
