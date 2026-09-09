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

use EWW\Dpf\Updates\HideRelationDetailStandaloneRowsUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class HideRelationDetailStandaloneRowsUpdateTest extends UnitTestCase
{
    public function testGetIdentifier()
    {
        $this->assertSame(
            'dpfHideRelationDetailStandaloneRows',
            (new HideRelationDetailStandaloneRowsUpdate())->getIdentifier()
        );
    }

    public function testComputeFixHidesAffectedRow()
    {
        $wizard = new HideRelationDetailStandaloneRowsUpdate();

        $this->assertSame(
            ['hidden' => 1],
            $wizard->computeFix(['index_name' => 'multivolume_doi', 'hidden' => 0])
        );
        $this->assertSame(
            ['hidden' => 1],
            $wizard->computeFix(['index_name' => 'series_zdb', 'hidden' => 0])
        );
    }

    public function testComputeFixSkipsAlreadyHiddenRow()
    {
        $wizard = new HideRelationDetailStandaloneRowsUpdate();

        $this->assertSame(
            [],
            $wizard->computeFix(['index_name' => 'multivolume_doi', 'hidden' => 1])
        );
    }

    public function testComputeFixSkipsUnrelatedRow()
    {
        $wizard = new HideRelationDetailStandaloneRowsUpdate();

        $this->assertSame(
            [],
            $wizard->computeFix(['index_name' => 'series_note0', 'hidden' => 0])
        );
    }
}
