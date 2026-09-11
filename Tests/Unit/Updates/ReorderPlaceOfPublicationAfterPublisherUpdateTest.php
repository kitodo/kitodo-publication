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

use EWW\Dpf\Updates\ReorderPlaceOfPublicationAfterPublisherUpdate;
use Nimut\TestingFramework\TestCase\UnitTestCase;

class ReorderPlaceOfPublicationAfterPublisherUpdateTest extends UnitTestCase
{
    /**
     * @var ReorderPlaceOfPublicationAfterPublisherUpdate
     */
    private $subject;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new ReorderPlaceOfPublicationAfterPublisherUpdate();
    }

    public function testIdentifierAndTitle()
    {
        $this->assertSame('dpfReorderPlaceOfPublicationAfterPublisher', $this->subject->getIdentifier());
        $this->assertStringContainsString('Erscheinungsort', $this->subject->getTitle());
    }

    public function testMovesPlaceToRightAfterPublisherWhenFarBehind()
    {
        // real test-server values (#2040): place_of_publication=117540, corporation_publisher=104192
        $this->assertSame(104193, $this->subject->computeNewPlaceSorting(117540, 104192));
    }

    public function testDoesNothingWhenAlreadyRightAfterPublisher()
    {
        $this->assertNull($this->subject->computeNewPlaceSorting(104193, 104192));
    }

    public function testMovesPlaceWhenItSortsBeforePublisher()
    {
        $this->assertSame(104193, $this->subject->computeNewPlaceSorting(100, 104192));
    }
}
