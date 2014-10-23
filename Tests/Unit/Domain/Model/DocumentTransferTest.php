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
 * Test case for class \EWW\Dpf\Domain\Model\DocumentTransfer.
 *
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class DocumentTransferTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {
	/**
	 * @var \EWW\Dpf\Domain\Model\DocumentTransfer
	 */
	protected $subject = NULL;

	protected function setUp() {
		$this->subject = new \EWW\Dpf\Domain\Model\DocumentTransfer();
	}

	protected function tearDown() {
		unset($this->subject);
	}

	/**
	 * @test
	 */
	public function getStatusReturnsInitialValueForInteger() {
		$this->assertSame(
			0,
			$this->subject->getStatus()
		);
	}

	/**
	 * @test
	 */
	public function setStatusForIntegerSetsStatus() {
		$this->subject->setStatus(12);

		$this->assertAttributeEquals(
			12,
			'status',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function getStartDateReturnsInitialValueForDateTime() {
		$this->assertEquals(
			NULL,
			$this->subject->getStartDate()
		);
	}

	/**
	 * @test
	 */
	public function setStartDateForDateTimeSetsStartDate() {
		$dateTimeFixture = new \DateTime();
		$this->subject->setStartDate($dateTimeFixture);

		$this->assertAttributeEquals(
			$dateTimeFixture,
			'startDate',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function getEndDateReturnsInitialValueForDateTime() {
		$this->assertEquals(
			NULL,
			$this->subject->getEndDate()
		);
	}

	/**
	 * @test
	 */
	public function setEndDateForDateTimeSetsEndDate() {
		$dateTimeFixture = new \DateTime();
		$this->subject->setEndDate($dateTimeFixture);

		$this->assertAttributeEquals(
			$dateTimeFixture,
			'endDate',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function getHttpStatusReturnsInitialValueForString() {
		$this->assertSame(
			'',
			$this->subject->getHttpStatus()
		);
	}

	/**
	 * @test
	 */
	public function setHttpStatusForStringSetsHttpStatus() {
		$this->subject->setHttpStatus('Conceived at T3CON10');

		$this->assertAttributeEquals(
			'Conceived at T3CON10',
			'httpStatus',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function getResponseReturnsInitialValueForString() {
		$this->assertSame(
			'',
			$this->subject->getResponse()
		);
	}

	/**
	 * @test
	 */
	public function setResponseForStringSetsResponse() {
		$this->subject->setResponse('Conceived at T3CON10');

		$this->assertAttributeEquals(
			'Conceived at T3CON10',
			'response',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function getErrorReturnsInitialValueForInteger() {
		$this->assertSame(
			0,
			$this->subject->getError()
		);
	}

	/**
	 * @test
	 */
	public function setErrorForIntegerSetsError() {
		$this->subject->setError(12);

		$this->assertAttributeEquals(
			12,
			'error',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function getParentDocumentReturnsInitialValueForDocument() {
		$this->assertEquals(
			NULL,
			$this->subject->getParentDocument()
		);
	}

	/**
	 * @test
	 */
	public function setParentDocumentForDocumentSetsParentDocument() {
		$parentDocumentFixture = new \EWW\Dpf\Domain\Model\Document();
		$this->subject->setParentDocument($parentDocumentFixture);

		$this->assertAttributeEquals(
			$parentDocumentFixture,
			'parentDocument',
			$this->subject
		);
	}
}
