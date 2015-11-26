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
	 * virtual
         * 
         * @var boolean
	 */
	protected $virtual = FALSE;
        
        
        /**
	 * metadataPage
	 *
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataPage>
	 * @cascade remove
	 */
	protected $metadataPage = NULL;
                
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
		$this->metadataPage = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
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
	 * Adds a MetadataPage
	 *
	 * @param \EWW\Dpf\Domain\Model\MetadataPage $metadataPage
	 * @return void
	 */
	public function addMetadataPage(\EWW\Dpf\Domain\Model\MetadataPage $metadataPage) {
		$this->metadataPage->attach($metadataPage);
	}

	/**
	 * Removes a MetadataPage
	 *
	 * @param \EWW\Dpf\Domain\Model\MetadataPage $metadataPageToRemove The MetadataPage to be removed
	 * @return void
	 */
	public function removeMetadataPage(\EWW\Dpf\Domain\Model\MetadataPage $metadataPageToRemove) {
		$this->metadataPage->detach($metadataPageToRemove);
	}

	/**
	 * Returns the metadataPage
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataPage> $metadataPage
	 */
	public function getMetadataPage() {
		return $this->metadataPage;
	}

	/**
	 * Sets the metadataPage
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataPage> $metadataPage
	 * @return void
	 */
	public function setMetadataPage(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $metadataPage) {
		$this->metadataPage = $metadataPage;
	}

        /**
         * Alias for function getMetadataPage()
         *
 	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataPage> $metadataPage
         */
        public function getChildren() {
           return $this->getMetadataPage();
        }

        /**
         * Returns the virtual
         * 
         * @return boolen
         */
        public function getVirtual() {
            return $this->virtual;
        }
        
        /**
         * Sets the virtual
         * 
         * @param boolean $virtual
         * @return void
         */
        public function setVirtual($virtual) {
            $this->virtual = $virtual;
        }
        
        
}