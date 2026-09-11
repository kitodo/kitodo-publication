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

use EWW\Dpf\Updates\ReorderReportAfterMonographInDropdownUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class ReorderReportAfterMonographInDropdownUpdateTest extends UnitTestCase
{
    /**
     * @var ReorderReportAfterMonographInDropdownUpdate
     */
    private $subject;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new ReorderReportAfterMonographInDropdownUpdate();
    }

    public function testIdentifierAndTitle()
    {
        $this->assertSame('dpfReorderReportAfterMonographInDropdown', $this->subject->getIdentifier());
        $this->assertStringContainsString('Monographie', $this->subject->getTitle());
    }

    public function testMovesReportJustAfterMonographWhenReportSortsBefore()
    {
        $this->assertSame(2730, $this->subject->computeNewReportSorting(2678, 2729));
    }

    public function testMovesReportJustAfterMonographWhenSortingIsEqual()
    {
        $this->assertSame(2730, $this->subject->computeNewReportSorting(2729, 2729));
    }

    public function testDoesNothingWhenReportAlreadySortsAfterMonograph()
    {
        $this->assertNull($this->subject->computeNewReportSorting(2730, 2729));
    }
}
