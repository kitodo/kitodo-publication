<?php

namespace EWW\Dpf\Tests\Unit\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case for class \EWW\Dpf\Domain\Model\MetadataPage.
 *
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class MetadataPageTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {
	/**
	 * @var \EWW\Dpf\Domain\Model\MetadataPage
	 */
	protected $subject = NULL;

	protected function setUp() {
		$this->subject = new \EWW\Dpf\Domain\Model\MetadataPage();
	}

	protected function tearDown() {
		unset($this->subject);
	}

	/**
	 * @test
	 */
	public function getTitleReturnsInitialValueForString() {
		$this->assertSame(
			'',
			$this->subject->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setTitleForStringSetsTitle() {
		$this->subject->setTitle('Conceived at T3CON10');

		$this->assertAttributeEquals(
			'Conceived at T3CON10',
			'title',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function getPageReturnsInitialValueForInteger() {
		$this->assertSame(
			0,
			$this->subject->getPage()
		);
	}

	/**
	 * @test
	 */
	public function setPageForIntegerSetsPage() {
		$this->subject->setPage(12);

		$this->assertAttributeEquals(
			12,
			'page',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function getDisplayTitleReturnsInitialValueForString() {
		$this->assertSame(
			'',
			$this->subject->getDisplayTitle()
		);
	}

	/**
	 * @test
	 */
	public function setDisplayTitleForStringSetsDisplayTitle() {
		$this->subject->setDisplayTitle('Conceived at T3CON10');

		$this->assertAttributeEquals(
			'Conceived at T3CON10',
			'displayTitle',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function getMetadataGroupReturnsInitialValueForMetadataGroup() {
		$newObjectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->assertEquals(
			$newObjectStorage,
			$this->subject->getMetadataGroup()
		);
	}

	/**
	 * @test
	 */
	public function setMetadataGroupForObjectStorageContainingMetadataGroupSetsMetadataGroup() {
		$metadataGroup = new \EWW\Dpf\Domain\Model\MetadataGroup();
		$objectStorageHoldingExactlyOneMetadataGroup = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$objectStorageHoldingExactlyOneMetadataGroup->attach($metadataGroup);
		$this->subject->setMetadataGroup($objectStorageHoldingExactlyOneMetadataGroup);

		$this->assertAttributeEquals(
			$objectStorageHoldingExactlyOneMetadataGroup,
			'metadataGroup',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function addMetadataGroupToObjectStorageHoldingMetadataGroup() {
		$metadataGroup = new \EWW\Dpf\Domain\Model\MetadataGroup();
		$metadataGroupObjectStorageMock = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', array('attach'), array(), '', FALSE);
		$metadataGroupObjectStorageMock->expects($this->once())->method('attach')->with($this->equalTo($metadataGroup));
		$this->inject($this->subject, 'metadataGroup', $metadataGroupObjectStorageMock);

		$this->subject->addMetadataGroup($metadataGroup);
	}

	/**
	 * @test
	 */
	public function removeMetadataGroupFromObjectStorageHoldingMetadataGroup() {
		$metadataGroup = new \EWW\Dpf\Domain\Model\MetadataGroup();
		$metadataGroupObjectStorageMock = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', array('detach'), array(), '', FALSE);
		$metadataGroupObjectStorageMock->expects($this->once())->method('detach')->with($this->equalTo($metadataGroup));
		$this->inject($this->subject, 'metadataGroup', $metadataGroupObjectStorageMock);

		$this->subject->removeMetadataGroup($metadataGroup);

	}
}
