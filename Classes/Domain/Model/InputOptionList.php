<?php
namespace Eww\Dpf\Domain\Model;


/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
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
 * InputOptionList
 */
class InputOptionList extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * name
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * displayName
	 *
	 * @var string
	 */
	protected $displayName = '';

	/**
	 * inputOptions
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Eww\Dpf\Domain\Model\InputOption>
	 */
	protected $inputOptions = NULL;

	/**
	 * __construct
	 */
	public function __construct() {
		//Do not remove the next line: It would break the functionality
		$this->initStorageObjects();
	}

	/**
	 * Initializes all ObjectStorage properties
	 * Do not modify this method!
	 * It will be rewritten on each save in the extension builder
	 * You may modify the constructor of this class instead
	 *
	 * @return void
	 */
	protected function initStorageObjects() {
		$this->inputOptions = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * Returns the name
	 *
	 * @return string $name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the name
	 *
	 * @param string $name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Returns the displayName
	 *
	 * @return string $displayName
	 */
	public function getDisplayName() {
		return $this->displayName;
	}

	/**
	 * Sets the displayName
	 *
	 * @param string $displayName
	 * @return void
	 */
	public function setDisplayName($displayName) {
		$this->displayName = $displayName;
	}

	/**
	 * Adds a InputOption
	 *
	 * @param \Eww\Dpf\Domain\Model\InputOption $inputOption
	 * @return void
	 */
	public function addInputOption(\Eww\Dpf\Domain\Model\InputOption $inputOption) {
		$this->inputOptions->attach($inputOption);
	}

	/**
	 * Removes a InputOption
	 *
	 * @param \Eww\Dpf\Domain\Model\InputOption $inputOptionToRemove The InputOption to be removed
	 * @return void
	 */
	public function removeInputOption(\Eww\Dpf\Domain\Model\InputOption $inputOptionToRemove) {
		$this->inputOptions->detach($inputOptionToRemove);
	}

	/**
	 * Returns the inputOptions
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Eww\Dpf\Domain\Model\InputOption> $inputOptions
	 */
	public function getInputOptions() {
		return $this->inputOptions;
	}

	/**
	 * Sets the inputOptions
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Eww\Dpf\Domain\Model\InputOption> $inputOptions
	 * @return void
	 */
	public function setInputOptions(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $inputOptions) {
		$this->inputOptions = $inputOptions;
	}

}