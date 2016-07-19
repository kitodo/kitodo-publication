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
 * Test case for class \EWW\Dpf\Domain\Model\Document.
 *
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class DocumentTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {
	/**
	 * @var \EWW\Dpf\Domain\Model\Document
	 */
	protected $subject = NULL;

	protected function setUp() {
		$this->subject = new \EWW\Dpf\Domain\Model\Document();
	}

	protected function tearDown() {
		unset($this->subject);
	}

	/**
	 * @test
	 */
	public function getXmlDataReturnsInitialValueForString() {
		$this->assertSame(
			'',
			$this->subject->getXmlData()
		);
	}

	/**
	 * @test
	 */
	public function setXmlDataForStringSetsXmlData() {
		$this->subject->setXmlData('Conceived at T3CON10');

		$this->assertAttributeEquals(
			'Conceived at T3CON10',
			'xmlData',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function getDocumentTypeReturnsInitialValueForDocumentType() {
		$this->assertEquals(
			NULL,
			$this->subject->getDocumentType()
		);
	}

	/**
	 * @test
	 */
	public function setDocumentTypeForDocumentTypeSetsDocumentType() {
		$documentTypeFixture = new \EWW\Dpf\Domain\Model\DocumentType();
		$this->subject->setDocumentType($documentTypeFixture);

		$this->assertAttributeEquals(
			$documentTypeFixture,
			'documentType',
			$this->subject
		);
	}
}
