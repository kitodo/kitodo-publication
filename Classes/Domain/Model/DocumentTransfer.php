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
 * DocumentTransfer
 */
class DocumentTransfer extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * status
	 *
	 * @var integer
	 */
	protected $status = 0;

	/**
	 * startDate
	 *
	 * @var \DateTime
	 */
	protected $startDate = NULL;

	/**
	 * endDate
	 *
	 * @var \DateTime
	 */
	protected $endDate = NULL;

	/**
	 * httpStatus
	 *
	 * @var string
	 */
	protected $httpStatus = '';

	/**
	 * response
	 *
	 * @var string
	 */
	protected $response = '';

	/**
	 * error
	 *
	 * @var integer
	 */
	protected $error = 0;

	/**
	 * parentDocument
	 *
	 * @var \EWW\Dpf\Domain\Model\Document
	 */
	protected $parentDocument = NULL;

	/**
	 * Returns the status
	 *
	 * @return integer $status
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * Sets the status
	 *
	 * @param integer $status
	 * @return void
	 */
	public function setStatus($status) {
		$this->status = $status;
	}

	/**
	 * Returns the error
	 *
	 * @return integer $error
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * Sets the error
	 *
	 * @param integer $error
	 * @return void
	 */
	public function setError($error) {
		$this->error = $error;
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
	 * Returns the httpStatus
	 *
	 * @return string $httpStatus
	 */
	public function getHttpStatus() {
		return $this->httpStatus;
	}

	/**
	 * Sets the httpStatus
	 *
	 * @param string $httpStatus
	 * @return void
	 */
	public function setHttpStatus($httpStatus) {
		$this->httpStatus = $httpStatus;
	}

	/**
	 * Returns the endDate
	 *
	 * @return \DateTime endDate
	 */
	public function getEndDate() {
		return $this->endDate;
	}

	/**
	 * Sets the endDate
	 *
	 * @param \DateTime $endDate
	 * @return \DateTime endDate
	 */
	public function setEndDate(\DateTime $endDate) {
		$this->endDate = $endDate;
	}

	/**
	 * Returns the startDate
	 *
	 * @return \DateTime startDate
	 */
	public function getStartDate() {
		return $this->startDate;
	}

	/**
	 * Sets the startDate
	 *
	 * @param \DateTime $startDate
	 * @return \DateTime startDate
	 */
	public function setStartDate(\DateTime $startDate) {
		$this->startDate = $startDate;
	}

	/**
	 * Returns the parentDocument
	 *
	 * @return \EWW\Dpf\Domain\Model\Document parentDocument
	 */
	public function getParentDocument() {
		return $this->parentDocument;
	}

	/**
	 * Sets the parentDocument
	 *
	 * @param \EWW\Dpf\Domain\Model\Document $parentDocument
	 * @return \EWW\Dpf\Domain\Model\Document parentDocument
	 */
	public function setParentDocument(\EWW\Dpf\Domain\Model\Document $parentDocument) {
		$this->parentDocument = $parentDocument;
	}

}