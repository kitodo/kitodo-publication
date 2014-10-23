<?php
namespace EWW\Dpf\Domain\Model;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014
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
 * FormPage
 */
class FormPage extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * title
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * page
	 *
	 * @var integer
	 */
	protected $page = 0;

	/**
	 * displayTitle
	 *
	 * @var string
	 */
	protected $displayTitle = '';

	/**
	 * medataGroup
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataGroup>
	 */
	protected $medataGroup = NULL;

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
		$this->medataGroup = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * Returns the title
	 *
	 * @return string $title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets the title
	 *
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Returns the page
	 *
	 * @return integer $page
	 */
	public function getPage() {
		return $this->page;
	}

	/**
	 * Sets the page
	 *
	 * @param integer $page
	 * @return void
	 */
	public function setPage($page) {
		$this->page = $page;
	}

	/**
	 * Returns the displayTitle
	 *
	 * @return string $displayTitle
	 */
	public function getDisplayTitle() {
		return $this->displayTitle;
	}

	/**
	 * Sets the displayTitle
	 *
	 * @param string $displayTitle
	 * @return void
	 */
	public function setDisplayTitle($displayTitle) {
		$this->displayTitle = $displayTitle;
	}

	/**
	 * Adds a MetadataGroup
	 *
	 * @param \EWW\Dpf\Domain\Model\MetadataGroup $medataGroup
	 * @return void
	 */
	public function addMedataGroup(\EWW\Dpf\Domain\Model\MetadataGroup $medataGroup) {
		$this->medataGroup->attach($medataGroup);
	}

	/**
	 * Removes a MetadataGroup
	 *
	 * @param \EWW\Dpf\Domain\Model\MetadataGroup $medataGroupToRemove The MetadataGroup to be removed
	 * @return void
	 */
	public function removeMedataGroup(\EWW\Dpf\Domain\Model\MetadataGroup $medataGroupToRemove) {
		$this->medataGroup->detach($medataGroupToRemove);
	}

	/**
	 * Returns the medataGroup
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataGroup> $medataGroup
	 */
	public function getMedataGroup() {
		return $this->medataGroup;
	}

	/**
	 * Sets the medataGroup
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataGroup> $medataGroup
	 * @return void
	 */
	public function setMedataGroup(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $medataGroup) {
		$this->medataGroup = $medataGroup;
	}

}