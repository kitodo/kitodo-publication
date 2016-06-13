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
 * Client
 */
class Client extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * project
     *
     * @var string
     * @validate NotEmpty
     */
    protected $project = '';

    /**
     * client
     *
     * @var string
     * @validate NotEmpty
     */
    protected $client = '';

    /**
     * ownerId
     *
     * @var string
     * @validate NotEmpty
     */
    protected $ownerId = '';

    /**
     * networkInitial
     *
     * @var string
     * @validate NotEmpty
     */
    protected $networkInitial = '';

    /**
     * libraryIdentifier
     *
     * @var string
     * @validate NotEmpty
     */
    protected $libraryIdentifier = '';

    /**
     * adminEmail
     *
     * @var string
     * @validate NotEmpty
     */
    protected $adminEmail = '';

    /**
     * Workaround to ensure unique URNs until URNs will be genarated by fedora.
     * @var string
     */
    protected $nissPartSearch = '';

    /**
     * Workaround to ensure unique URNs until URNs will be genarated by fedora.
     * @var string
     */
    protected $nissPartReplace = '';

    /**
     * Workaround to ensure unique URNs until URNs will be genarated by fedora.
     * @var boolean
     */
    protected $replaceNissPart = false;

    /**
     * Returns the project
     *
     * @return string $project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Sets the project
     *
     * @param string $project
     * @return void
     */
    public function setProject($project)
    {
        $this->project = $project;
    }

    /**
     * Returns the client
     *
     * @return string $client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Sets the client
     *
     * @param string $client
     * @return void
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * Returns the networkInitial
     *
     * @return string $networkInitial
     */
    public function getNetworkInitial()
    {
        return $this->networkInitial;
    }

    /**
     * Sets the networkInitial
     *
     * @param string $networkInitial
     * @return void
     */
    public function setNetworkInitial($networkInitial)
    {
        $this->networkInitial = $networkInitial;
    }

    /**
     * Returns the libraryIdentifier
     *
     * @return string $libraryIdentifier
     */
    public function getLibraryIdentifier()
    {
        return $this->libraryIdentifier;
    }

    /**
     * Sets the libraryIdentifier
     *
     * @param string $libraryIdentifier
     * @return void
     */
    public function setLibraryIdentifier($libraryIdentifier)
    {
        $this->libraryIdentifier = $libraryIdentifier;
    }

    /**
     * Gets the ownerId
     *
     * @return string
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * Sets the ownerId
     *
     * @param string $ownerId
     * @return void
     */
    public function setOwnerId($ownerId)
    {
        $this->ownerId = $ownerId;
    }

    /**
     * Gets the adminEmail
     *
     * @return string
     */
    public function getAdminEmail()
    {
        return $this->adminEmail;
    }

    /**
     * Sets the adminEmail
     *
     * @return string
     */
    public function setAdminEmail($adminEmail)
    {
        $this->adminEmail = $adminEmail;
    }

    /**
     * Returns the nissPartSearch
     *
     * @return string $nissPartSearch
     */
    public function getNissPartSearch()
    {
        return $this->nissPartSearch;
    }

    /**
     * Sets the nissPartSearch
     *
     * @param string $nissPartSearch
     * @return void
     */
    public function setNissPartSearch($nissPartSearch)
    {
        $this->nissPartSearch = $nissPartSearch;
    }

    /**
     * Returns the nissPartReplace
     *
     * @return string $nissPartReplace
     */
    public function getNissPartReplace()
    {
        return $this->nissPartReplace;
    }

    /**
     * Sets the nissPartReplace
     *
     * @param string $nissPartReplace
     * @return void
     */
    public function setNissPartReplace($nissPartReplace)
    {
        $this->nissPartReplace = $nissPartReplace;
    }

    /**
     * Returns the replaceNissPart
     *
     * @return boolean $replaceNissPart
     */
    public function getReplaceNissPart()
    {
        return $this->replaceNissPart;
    }

    /**
     * Sets the replaceNissPart
     *
     * @param boolean $replaceNissPart
     * @return void
     */
    public function setReplaceNissPart($replaceNissPart)
    {
        $this->replaceNissPart = $replaceNissPart;
    }

}
