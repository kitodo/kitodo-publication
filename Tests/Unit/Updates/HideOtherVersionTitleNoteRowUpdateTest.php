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

use EWW\Dpf\Updates\HideOtherVersionTitleNoteRowUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class HideOtherVersionTitleNoteRowUpdateTest extends UnitTestCase
{
    public function testGetIdentifier()
    {
        $this->assertSame('dpfHideOtherVersionTitleNoteRow', (new HideOtherVersionTitleNoteRowUpdate())->getIdentifier());
    }

    public function testComputeFixHidesUnhiddenAffectedRow()
    {
        $update = new HideOtherVersionTitleNoteRowUpdate();
        $this->assertSame(
            ['hidden' => 1],
            $update->computeFix(['uid' => 225, 'index_name' => 'otherVersion00', 'hidden' => 0])
        );
        $this->assertSame(
            ['hidden' => 1],
            $update->computeFix(['uid' => 224, 'index_name' => 'otherVersion0', 'hidden' => 0])
        );
    }

    public function testComputeFixNoOpsAlreadyHiddenOrUnaffectedRow()
    {
        $update = new HideOtherVersionTitleNoteRowUpdate();
        $this->assertSame(
            [],
            $update->computeFix(['uid' => 225, 'index_name' => 'otherVersion00', 'hidden' => 1])
        );
        $this->assertSame(
            [],
            $update->computeFix(['uid' => 253, 'index_name' => 'doi', 'hidden' => 0])
        );
    }
}
