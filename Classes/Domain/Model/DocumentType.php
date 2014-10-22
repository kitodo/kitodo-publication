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
 * DocumentType
 */
class DocumentType extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * title
	 *
	 * @var string
	 */
	protected $title = '';

	/**
	 * metadataGroup
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataGroup>
	 */
	protected $metadataGroup = NULL;

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
		$this->metadataGroup = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * Adds a MetadataGroup
	 *
	 * @param \EWW\Dpf\Domain\Model\MetadataGroup $metadataGroup
	 * @return void
	 */
	public function addMetadataGroup(\EWW\Dpf\Domain\Model\MetadataGroup $metadataGroup) {
		$this->metadataGroup->attach($metadataGroup);
	}

	/**
	 * Removes a MetadataGroup
	 *
	 * @param \EWW\Dpf\Domain\Model\MetadataGroup $metadataGroupToRemove The MetadataGroup to be removed
	 * @return void
	 */
	public function removeMetadataGroup(\EWW\Dpf\Domain\Model\MetadataGroup $metadataGroupToRemove) {
		$this->metadataGroup->detach($metadataGroupToRemove);
	}

	/**
	 * Returns the metadataGroup
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataGroup> $metadataGroup
	 */
	public function getMetadataGroup() {
		return $this->metadataGroup;
	}

	/**
	 * Sets the metadataGroup
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataGroup> $metadataGroup
	 * @return void
	 */
	public function setMetadataGroup(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $metadataGroup) {
		$this->metadataGroup = $metadataGroup;
	}

}