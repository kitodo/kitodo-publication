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
 * Document
 */
class Document extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

  
        /**
	 * crdate
	 *
	 * @var DateTime
	 */
	protected $crdate;        
  
        /**
	 * title
	 *
	 * @var string
	 */
	protected $title = '';
  
	/**
	 * xmlData
	 *
	 * @var string
	 */
	protected $xmlData = '';

	/**
	 * documentType
	 *
	 * @var \EWW\Dpf\Domain\Model\DocumentType
	 */
	protected $documentType = NULL;

        
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
	 * Returns the xmlData
	 *
	 * @return string $xmlData
	 */
	public function getXmlData() {
		return $this->xmlData;
	}

	/**
	 * Sets the xmlData
	 *
	 * @param string $xmlData
	 * @return void
	 */
	public function setXmlData($xmlData) {
		$this->xmlData = $xmlData;
	}

	/**
	 * Returns the documentType
	 *
	 * @return \EWW\Dpf\Domain\Model\DocumentType $documentType
	 */
	public function getDocumentType() {
		return $this->documentType;
	}

	/**
	 * Sets the documentType
	 *
	 * @param \EWW\Dpf\Domain\Model\DocumentType $documentType
	 * @return void
	 */
	public function setDocumentType(\EWW\Dpf\Domain\Model\DocumentType $documentType) {
		$this->documentType = $documentType;
	}
        
        /**
         * 
         * @return DateTime
         */        
        public function getCrdate() {
          return $this->crdate;
        }
        
}