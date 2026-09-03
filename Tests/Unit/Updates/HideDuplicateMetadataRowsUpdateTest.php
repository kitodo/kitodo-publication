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

use EWW\Dpf\Updates\HideDuplicateMetadataRowsUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class HideDuplicateMetadataRowsUpdateTest extends UnitTestCase
{
    public function testHidesPublizierendeInstitutionRow()
    {
        $wizard = new HideDuplicateMetadataRowsUpdate();

        $fix = $wizard->computeFix(['uid' => 36, 'index_name' => 'university_publisher', 'hidden' => 0]);

        $this->assertSame(['hidden' => 1], $fix);
    }

    public function testHidesErscheinungsortQuelleRow()
    {
        $wizard = new HideDuplicateMetadataRowsUpdate();

        $fix = $wizard->computeFix(['uid' => 532, 'index_name' => 'place_of_publication_host', 'hidden' => 0]);

        $this->assertSame(['hidden' => 1], $fix);
    }

    public function testAlreadyHiddenRowNeedsNoFix()
    {
        $wizard = new HideDuplicateMetadataRowsUpdate();

        $fix = $wizard->computeFix(['uid' => 36, 'index_name' => 'university_publisher', 'hidden' => 1]);

        $this->assertSame([], $fix);
    }

    public function testUnrelatedRowNeedsNoFix()
    {
        $wizard = new HideDuplicateMetadataRowsUpdate();

        $fix = $wizard->computeFix(['uid' => 37, 'index_name' => 'corporation_publisher', 'hidden' => 0]);

        $this->assertSame([], $fix);
    }
}
