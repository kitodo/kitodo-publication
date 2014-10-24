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
 * Test case for class \EWW\Dpf\Domain\Model\MetadataObject.
 *
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class MetadataObjectTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {
	/**
	 * @var \EWW\Dpf\Domain\Model\MetadataObject
	 */
	protected $subject = NULL;

	protected function setUp() {
		$this->subject = new \EWW\Dpf\Domain\Model\MetadataObject();
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
	public function getMaxIterationReturnsInitialValueForInteger() {
		$this->assertSame(
			0,
			$this->subject->getMaxIteration()
		);
	}

	/**
	 * @test
	 */
	public function setMaxIterationForIntegerSetsMaxIteration() {
		$this->subject->setMaxIteration(12);

		$this->assertAttributeEquals(
			12,
			'maxIteration',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function getMandatoryReturnsInitialValueForBoolean() {
		$this->assertSame(
			FALSE,
			$this->subject->getMandatory()
		);
	}

	/**
	 * @test
	 */
	public function setMandatoryForBooleanSetsMandatory() {
		$this->subject->setMandatory(TRUE);

		$this->assertAttributeEquals(
			TRUE,
			'mandatory',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function getMappingReturnsInitialValueForString() {
		$this->assertSame(
			'',
			$this->subject->getMapping()
		);
	}

	/**
	 * @test
	 */
	public function setMappingForStringSetsMapping() {
		$this->subject->setMapping('Conceived at T3CON10');

		$this->assertAttributeEquals(
			'Conceived at T3CON10',
			'mapping',
			$this->subject
		);
	}

	/**
	 * @test
	 */
	public function getInputFieldReturnsInitialValueForInteger() {
		$this->assertSame(
			0,
			$this->subject->getInputField()
		);
	}

	/**
	 * @test
	 */
	public function setInputFieldForIntegerSetsInputField() {
		$this->subject->setInputField(12);

		$this->assertAttributeEquals(
			12,
			'inputField',
			$this->subject
		);
	}
}
