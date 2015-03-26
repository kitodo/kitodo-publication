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
 * Client
 */
class Client extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * project
	 *
	 * @var string
	 */
	protected $project = '';

	/**
	 * client
	 *
	 * @var string
	 */
	protected $client = '';
        
        /**
	 * ownerId
	 *
	 * @var string
	 */
	protected $ownerId = '';
        
        
	/**
	 * Returns the project
	 *
	 * @return string $project
	 */
	public function getProject() {
		return $this->project;
	}

	/**
	 * Sets the project
	 *
	 * @param string $project
	 * @return void
	 */
	public function setProject($project) {
		$this->project = $project;
	}

	/**
	 * Returns the client
	 *
	 * @return string $client
	 */
	public function getClient() {
		return $this->client;
	}

	/**
	 * Sets the client
	 *
	 * @param string $client
	 * @return void
	 */
	public function setClient($client) {
		$this->client = $client;
	}
        
        
        /**
	 * Gets the ownerId
	 *	
	 * @return string
	 */
        public function getOwnerId() {
          return $this->ownerId;
        }

        
        /**
	 * Sets the ownerId
	 *
	 * @param string $ownerId
	 * @return void
	 */
        public function setOwnerId($ownerId) {
          $this->ownerId = $ownerId;
        }
}