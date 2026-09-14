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

use EWW\Dpf\Updates\MoveInstitutionContributorAfterContributorUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class MoveInstitutionContributorAfterContributorUpdateTest extends UnitTestCase
{
    /**
     * @var MoveInstitutionContributorAfterContributorUpdate
     */
    private $subject;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new MoveInstitutionContributorAfterContributorUpdate();
    }

    public function testIdentifierAndTitle()
    {
        $this->assertSame('dpfMoveInstitutionContributorAfterContributor', $this->subject->getIdentifier());
        $this->assertStringContainsString('Beitragende', $this->subject->getTitle());
    }

    public function testMovesInstitutionToRightAfterContributorWhenFarBehind()
    {
        // real test-server values (#2047): corporation_contributor=114816, contributor=92160
        $this->assertSame(92161, $this->subject->computeNewSorting(114816, 92160));
    }

    public function testDoesNothingWhenAlreadyRightAfterContributor()
    {
        $this->assertNull($this->subject->computeNewSorting(92161, 92160));
    }

    public function testMovesInstitutionWhenItSortsBeforeContributor()
    {
        $this->assertSame(92161, $this->subject->computeNewSorting(100, 92160));
    }
}
