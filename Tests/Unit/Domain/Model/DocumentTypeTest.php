<?php

namespace EWW\Dpf\Tests\Unit\Domain\Model;

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

/**
 * Test case for class \EWW\Dpf\Domain\Model\DocumentType.
 *
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class DocumentTypeTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {
	/**
	 * @var \EWW\Dpf\Domain\Model\DocumentType
	 */
	protected $subject = NULL;

	protected function setUp() {
		$this->subject = new \EWW\Dpf\Domain\Model\DocumentType();
	}

	protected function tearDown() {
		unset($this->subject);
	}

	/**
	 * @test
	 */
	public function getNameReturnsInitialValueForString() {
		$this->assertSame(
			'',
			$this->subject->getName()
		);
	}

	/**
	 * @test
	 */
	public function setNameForStringSetsName() {
		$this->subject->setName('Conceived at T3CON10');

		$this->assertAttributeEquals(
			'Conceived at T3CON10',
			'name',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function getDisplayNameReturnsInitialValueForString() {
		$this->assertSame(
			'',
			$this->subject->getDisplayName()
		);
	}

	/**
	 * @test
	 */
	public function setDisplayNameForStringSetsDisplayName() {
		$this->subject->setDisplayName('Conceived at T3CON10');

		$this->assertAttributeEquals(
			'Conceived at T3CON10',
			'displayName',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function getMetadataPageReturnsInitialValueForMetadataPage() {
		$newObjectStorage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$this->assertEquals(
			$newObjectStorage,
			$this->subject->getMetadataPage()
		);
	}

	/**
	 * @test
	 */
	public function setMetadataPageForObjectStorageContainingMetadataPageSetsMetadataPage() {
		$metadataPage = new \EWW\Dpf\Domain\Model\MetadataPage();
		$objectStorageHoldingExactlyOneMetadataPage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$objectStorageHoldingExactlyOneMetadataPage->attach($metadataPage);
		$this->subject->setMetadataPage($objectStorageHoldingExactlyOneMetadataPage);

		$this->assertAttributeEquals(
			$objectStorageHoldingExactlyOneMetadataPage,
			'metadataPage',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function addMetadataPageToObjectStorageHoldingMetadataPage() {
		$metadataPage = new \EWW\Dpf\Domain\Model\MetadataPage();
		$metadataPageObjectStorageMock = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', array('attach'), array(), '', FALSE);
		$metadataPageObjectStorageMock->expects($this->once())->method('attach')->with($this->equalTo($metadataPage));
		$this->inject($this->subject, 'metadataPage', $metadataPageObjectStorageMock);

		$this->subject->addMetadataPage($metadataPage);
	}

	/**
	 * @test
	 */
	public function removeMetadataPageFromObjectStorageHoldingMetadataPage() {
		$metadataPage = new \EWW\Dpf\Domain\Model\MetadataPage();
		$metadataPageObjectStorageMock = $this->getMock('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', array('detach'), array(), '', FALSE);
		$metadataPageObjectStorageMock->expects($this->once())->method('detach')->with($this->equalTo($metadataPage));
		$this->inject($this->subject, 'metadataPage', $metadataPageObjectStorageMock);

		$this->subject->removeMetadataPage($metadataPage);

	}
}
