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

use EWW\Dpf\Updates\FixMissingPlaceOfPublicationFormatUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class FixMissingPlaceOfPublicationFormatUpdateTest extends UnitTestCase
{
    public function testSetsFormatOnPlaceOfPublicationRow()
    {
        $wizard = new FixMissingPlaceOfPublicationFormatUpdate();

        $fix = $wizard->computeFix([
            'uid' => 531,
            'index_name' => 'place_of_publication',
            'format' => 0,
            'format_type' => '',
        ]);

        $this->assertSame(['format' => 1, 'format_type' => 'MODS'], $fix);
    }

    public function testSetsFormatOnPlaceOfPublicationHostRow()
    {
        $wizard = new FixMissingPlaceOfPublicationFormatUpdate();

        $fix = $wizard->computeFix([
            'uid' => 532,
            'index_name' => 'place_of_publication_host',
            'format' => 0,
            'format_type' => null,
        ]);

        $this->assertSame(['format' => 1, 'format_type' => 'MODS'], $fix);
    }

    public function testAlreadyFixedRowNeedsNoFix()
    {
        $wizard = new FixMissingPlaceOfPublicationFormatUpdate();

        $fix = $wizard->computeFix([
            'uid' => 531,
            'index_name' => 'place_of_publication',
            'format' => 1,
            'format_type' => 'MODS',
        ]);

        $this->assertSame([], $fix);
    }

    public function testUnrelatedRowNeedsNoFix()
    {
        $wizard = new FixMissingPlaceOfPublicationFormatUpdate();

        $fix = $wizard->computeFix([
            'uid' => 300,
            'index_name' => 'original_place',
            'format' => 0,
            'format_type' => '',
        ]);

        $this->assertSame([], $fix);
    }
}
