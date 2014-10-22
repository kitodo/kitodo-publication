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
 * MetadataGroup
 */
class MetadataGroup extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * title
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * mandatory
	 *
	 * @var boolean
	 */
	protected $mandatory = FALSE;

	/**
	 * maxIteration
	 *
	 * @var integer
	 */
	protected $maxIteration = 0;

	/**
	 * parentGroup
	 *
	 * @var \EWW\Dpf\Domain\Model\MetadataGroup
	 */
	protected $parentGroup = NULL;

	/**
	 * metadataObject
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataObject>
	 * @cascade remove
	 */
	protected $metadataObject = NULL;

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
	 * Returns the mandatory
	 *
	 * @return boolean $mandatory
	 */
	public function getMandatory() {
		return $this->mandatory;
	}

	/**
	 * Sets the mandatory
	 *
	 * @param boolean $mandatory
	 * @return void
	 */
	public function setMandatory($mandatory) {
		$this->mandatory = $mandatory;
	}

	/**
	 * Returns the boolean state of mandatory
	 *
	 * @return boolean
	 */
	public function isMandatory() {
		return $this->mandatory;
	}

	/**
	 * Returns the maxIteration
	 *
	 * @return integer $maxIteration
	 */
	public function getMaxIteration() {
		return $this->maxIteration;
	}

	/**
	 * Sets the maxIteration
	 *
	 * @param integer $maxIteration
	 * @return void
	 */
	public function setMaxIteration($maxIteration) {
		$this->maxIteration = $maxIteration;
	}

	/**
	 * Returns the parentGroup
	 *
	 * @return \EWW\Dpf\Domain\Model\MetadataGroup $parentGroup
	 */
	public function getParentGroup() {
		return $this->parentGroup;
	}

	/**
	 * Sets the parentGroup
	 *
	 * @param \EWW\Dpf\Domain\Model\MetadataGroup $parentGroup
	 * @return void
	 */
	public function setParentGroup(\EWW\Dpf\Domain\Model\MetadataGroup $parentGroup) {
		$this->parentGroup = $parentGroup;
	}

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
		$this->metadataObject = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * Adds a MetadataObject
	 *
	 * @param \EWW\Dpf\Domain\Model\MetadataObject $metadataObject
	 * @return void
	 */
	public function addMetadataObject(\EWW\Dpf\Domain\Model\MetadataObject $metadataObject) {
		$this->metadataObject->attach($metadataObject);
	}

	/**
	 * Removes a MetadataObject
	 *
	 * @param \EWW\Dpf\Domain\Model\MetadataObject $metadataObjectToRemove The MetadataObject to be removed
	 * @return void
	 */
	public function removeMetadataObject(\EWW\Dpf\Domain\Model\MetadataObject $metadataObjectToRemove) {
		$this->metadataObject->detach($metadataObjectToRemove);
	}

	/**
	 * Returns the metadataObject
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataObject> $metadataObject
	 */
	public function getMetadataObject() {
		return $this->metadataObject;
	}

	/**
	 * Sets the metadataObject
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataObject> $metadataObject
	 * @return void
	 */
	public function setMetadataObject(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $metadataObject) {
		$this->metadataObject = $metadataObject;
	}

}