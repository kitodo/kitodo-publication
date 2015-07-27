<?php
namespace EWW\Dpf\Domain\Model;


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
 * DocumentTransferLog
 */
class DocumentTransferLog extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * date
	 *
	 * @var \DateTime
	 */
	protected $date = NULL;

	/**
	 * response
	 *
	 * @var string
	 */
	protected $response = '';

	/**
	 * curlError
	 *
	 * @var string
	 */
	protected $curlError = '';

	/**
	 * documentUid
	 *
	 * @var integer
	 */
	protected $documentUid;

        /**
	 * objectIdentifier
	 *
	 * @var string
	 */
	protected $objectIdentifier;
                
        /**
	 * action
	 *
	 * @var string
	 */
	protected $action;
        
	/**
	 * Returns the date
	 *
	 * @return \DateTime $date
	 */
	public function getDate() {
		return $this->date;
	}

	/**
	 * Sets the date
	 *
	 * @param \DateTime $date
	 * @return void
	 */
	public function setDate(\DateTime $date) {
		$this->date = $date;
	}

	/**
	 * Returns the response
	 *
	 * @return string $response
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Sets the response
	 *
	 * @param string $response
	 * @return void
	 */
	public function setResponse($response) {
		$this->response = $response;
	}

	/**
	 * Returns the curlError
	 *
	 * @return string $curlError
	 */
	public function getCurlError() {
		return $this->curlError;
	}

	/**
	 * Sets the curlError
	 *
	 * @param string $curlError
	 * @return void
	 */
	public function setCurlError($curlError) {
		$this->curlError = $curlError;
	}

	/**
	 * Returns the documentUid
	 *
	 * @return integer $documentUid
	 */
	public function getDocumentUid() {
		return $this->documentUid;
	}

	/**
	 * Sets the documentUid
	 *
	 * @param integer $documentUid
	 * @return void
	 */
	public function setDocumentUid($documentUid) {
		$this->documentUid = $documentUid;
	}
        
        /**
	 * Returns the objectIdentifier
	 *
	 * @return string $objectIdentifier
	 */
	public function getObjectIdentifier() {
		return $this->objectIdentifier;
	}
        
        /**
	 * Sets the objectIdentifier
	 *
	 * @param string $objectIdentifier
	 * @return void
	 */
	public function setObjectIdentifier($objectIdentifier) {
		$this->objectIdentifier = $objectIdentifier;
	}

        /**
	 * Returns the action
	 *
	 * @return string $action
	 */
	public function getAction() {
		return $this->action;
	}
        
        /**
	 * Sets the action
	 *
	 * @param string $action
	 * @return void
	 */
	public function setAction($action) {
		$this->action = $action;
	}
        
        
}