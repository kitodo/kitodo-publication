<?php
namespace EWW\Dpf\Domain\Model;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
     * swordHost
     *
     * @var string
     */
    protected $swordHost = '';

    /**
     * swordUser
     *
     * @var string
     */
    protected $swordUser = '';

    /**
     * swordPassword
     *
     * @var string
     */
    protected $swordPassword = '';

    /**
     * swordCollectionNamespace
     *
     * @var string
     */
    protected $swordCollectionNamespace = '';

    /**
     * fedoraHost
     *
     * @var string
     */
    protected $fedoraHost = '';

    /**
     * fedoraUser
     *
     * @var string
     */
    protected $fedoraUser = '';

    /**
     * fedoraPassword
     *
     * @var string
     */
    protected $fedoraPassword = '';

    /**
     * elasticSearchHost
     *
     * @var string
     */
    protected $elasticSearchHost = '';

    /**
     * elasticSearchPort
     *
     * @var string
     */
    protected $elasticSearchPort = '';

    /**
     * uploadDirectory
     *
     * @var string
     */
    protected $uploadDirectory = '';

    /**
     * uploadDomain
     *
     * @var string
     */
    protected $uploadDomain = '';

    /**
     * adminNewDocumentNotificationSubject
     *
     * @var string
     */
    protected $adminNewDocumentNotificationSubject = '';

    /**
     * adminNewDocumentNotificationBody
     *
     * @var string
     */
    protected $adminNewDocumentNotificationBody = '';

    /**
     * submitterNewDocumentNotificationSubject
     *
     * @var string
     */
    protected $submitterNewDocumentNotificationSubject = '';

    /**
     * submitterNewDocumentNotificationBody
     *
     * @var string
     */
    protected $submitterNewDocumentNotificationBody = '';

    /**
     * submitterIngestNotificationSubject
     *
     * @var string
     */
    protected $submitterIngestNotificationSubject = '';

    /**
     * submitterIngestNotificationBody
     *
     * @var string
     */
    protected $submitterIngestNotificationBody = '';
    
    /**
     * adminRegisterDocumentNotificationSubject
     *
     * @var string
     */
    protected $adminRegisterDocumentNotificationSubject = '';

    /**
     * adminRegisterDocumentNotificationBody
     *
     * @var string
     */
    protected $adminRegisterDocumentNotificationBody = '';

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

    /**
     * Returns the swordHost
     *
     * @return string $swordHost
     */
    public function getSwordHost()
    {
        return $this->swordHost;
    }

    /**
     * Sets the swordHost
     *
     * @var string $swordHost
     * @return void
     */
    public function setSwordHost($swordHost)
    {
        $this->swordHost = $swordHost;
    }

    /**
     * Returns the swordUser
     *
     * @return string $swordUser
     */
    public function getSwordUser()
    {
        return $this->swordUser;
    }

    /**
     * Sets the swordUser
     *
     * @var string $swordUser
     * @return void
     */
    public function setSwordUser($swordUser)
    {
        $this->swordUser = $swordUser;
    }

    /**
     * Returns the swordPassword
     *
     * @return string $swordPassword
     */
    public function getSwordPassword()
    {
        return $this->swordPassword;
    }
    
    /**
     * Sets the swordPassword
     *
     * @var string $swordPassword
     * @return void
     */
    public function setSwordPassword($swordPassword)
    {
        $this->swordPassword = $swordPassword;
    }

    /**
     * Returns the swordCollectionNamespace
     *
     * @return string $swordCollectionNamespace
     */
    public function getSwordCollectionNamespace()
    {
        return $this->swordCollectionNamespace;
    }

    /**
     * Sets the swordCollectionNamespace
     *
     * @var string $swordCollectionNamespace
     * @return void
     */
    public function setSwordCollectionNamespace($swordCollectionNamespace)
    {
        $this->swordCollectionNamespace = $swordCollectionNamespace;
    }

    /**
     * Returns the fedoraHost
     *
     * @return string $fedoraHost
     */
    public function getFedoraHost()
    {
        return $this->fedoraHost;
    }

    /**
     * Sets the fedoraHost
     *
     * @var string $fedoraHost
     * @return void
     */
    public function setFedoraHost($fedoraHost)
    {
        $this->fedoraHost = $fedoraHost;
    }

    /**
     * Returns the fedoraUser
     *
     * @return string $fedoraUser
     */
    public function getFedoraUser()
    {
        return $this->fedoraUser;
    }

    /**
     * Sets the fedoraUser
     *
     * @var string $fedoraUser
     * @return void
     */
    public function setFedoraUser($fedoraUser)
    {
        $this->fedoraUser = $fedoraUser;
    }

    /**
     * Returns the fedoraPassword
     *
     * @return string $fedoraPassword
     */
    public function getFedoraPassword()
    {
        return $this->fedoraPassword;
    }

    /**
     * Sets the fedoraPassword
     *
     * @var string $fedoraPassword
     * @return void
     */
    public function setFedoraPassword($fedoraPassword)
    {
        $this->fedoraPassword = $fedoraPassword;
    }

    /**
     * Returns the elasticSearchHost
     *
     * @return string $elasticSearchHost
     */
    public function getElasticSearchHost()
    {
        return $this->elasticSearchHost;
    }

    /**
     * Sets the elasticSearchHost
     *
     * @var string $elasticSearchHost
     * @return void
     */
    public function setElasticSearchHost($elasticSearchHost)
    {
        $this->elasticSearchHost = $elasticSearchHost;
    }

    /**
     * Returns the elasticSearchPort
     *
     * @return string $elasticSearchPort
     */
    public function getElasticSearchPort()
    {
        return $this->elasticSearchPort;
    }

    /**
     * Sets the elasticSearchPort
     *
     * @var string $elasticSearchPort
     * @return void
     */
    public function setElasticSearchPort($elasticSearchPort)
    {
        $this->elasticSearchPort = $elasticSearchPort;
    }

    /**
     * Returns the uploadDirectory
     *
     * @return string $uploadDirectory
     */
    public function getUploadDirectory()
    {
        return $this->uploadDirectory;
    }

    /**
     * Sets the uploadDirectory
     *
     * @var string $uploadDirectory
     * @return void
     */
    public function setUploadDirectory($uploadDirectory)
    {
        $this->uploadDirectory = $uploadDirectory;
    }

    /**
     * Returns the uploadDomain
     *
     * @return string $uploadDomain
     */
    public function getUploadDomain()
    {
        return $this->uploadDomain;
    }

    /**
     * Sets the uploadDomain
     *
     * @var string $uploadDomain
     * @return void
     */
    public function setUploadDomain($uploadDomain)
    {
        $this->uploadDomain = $uploadDomain;
    }


    /**
     * Gets the submitterIngestNotificationSubject
     *
     * @return string
     */
    public function getSubmitterIngestNotificationSubject()
    {
        return $this->submitterIngestNotificationSubject;
    }

    /**
     * Sets the submitterIngestNotificationSubject
     *
     * @var string $submitterIngestNotificationSubject
     * @return void
     */
    public function setSubmitterIngestNotificationSubject($submitterIngestNotificationSubject)
    {
        $this->submitterIngestNotificationSubject = $submitterIngestNotificationSubject;
    }

    /**
     * Gets the submitterIngestNotificationBody
     *
     * @return string
     */
    public function getSubmitterIngestNotificationBody()
    {
        return $this->submitterIngestNotificationBody;
    }

    /**
     * Sets the submitterIngestNotificationBody
     *
     * @var string $submitterIngestNotificationBody
     * @return void
     */
    public function setSubmitterIngestNotificationBody($submitterIngestNotificationBody)
    {
        $this->submitterIngestNotificationBody = $submitterIngestNotificationBody;
    }

    /**
     * Gets the submitterNewDocumentNotificationSubject
     *
     * @return string
     */
    public function getSubmitterNewDocumentNotificationSubject()
    {
        return $this->submitterNewDocumentNotificationSubject;
    }

    /**
     * Sets the submitterNewDocumentNotificationSubject
     *
     * @var string $submitterNewDocumentNotificationSubject
     * @return void
     */
    public function setSubmitterNewDocumentNotificationSubject($submitterNewDocumentNotificationSubject)
    {
        $this->submitterNewDocumentNotificationSubject = $submitterNewDocumentNotificationSubject;
    }

    /**
     * Gets the submitterNewDocumentNotificationBody
     *
     * @return string
     */
    public function getSubmitterNewDocumentNotificationBody()
    {
        return $this->submitterNewDocumentNotificationBody;
    }

    /**
     * Sets the submitterNewDocumentNotificationBody
     *
     * @var string $submitterNewDocumentNotificationBody
     * @return void
     */
    public function setSubmitterNewDocumentNotificationBody($submitterNewDocumentNotificationBody)
    {
        $this->submitterNewDocumentNotificationBody = $submitterNewDocumentNotificationBody;
    }

    /**
     * Gets the adminNewDocumentNotificationSubject
     *
     * @return string
     */
    public function getAdminNewDocumentNotificationSubject()
    {
        return $this->adminNewDocumentNotificationSubject;
    }

    /**
     * Sets the adminNewDocumentNotificationSubject
     *
     * @var string $adminNewDocumentNotificationSubject
     * @return void
     */
    public function setAdminNewDocumentNotificationSubject($adminNewDocumentNotificationSubject)
    {
        $this->adminNewDocumentNotificationSubject = $adminNewDocumentNotificationSubject;
    }

    /**
     * Gets the adminNewDocumentNotificationBody
     *
     * @return string
     */
    public function getAdminNewDocumentNotificationBody()
    {
        return $this->adminNewDocumentNotificationBody;
    }

    /**
     * Sets the adminNewDocumentNotificationBody
     *
     * @var string $adminNewDocumentNotificationBody
     * @return void
     */
    public function setAdminNewDocumentNotificationBody($adminNewDocumentNotificationBody)
    {
        $this->adminNewDocumentNotificationBody = $adminNewDocumentNotificationBody;
    }

    /**
     * Gets the adminRegisterDocumentNotificationSubject
     *
     * @return string
     */
    public function getAdminRegisterDocumentNotificationSubject()
    {
        return $this->adminRegisterDocumentNotificationSubject;
    }

    /**
     * Sets the adminRegisterDocumentNotificationSubject
     *
     * @var string $adminRegisterDocumentNotificationSubject
     * @return void
     */
    public function setAdminRegisterDocumentNotificationSubject($adminRegisterDocumentNotificationSubject)
    {
        $this->adminRegisterDocumentNotificationSubject = $adminRegisterDocumentNotificationSubject;
    }

    /**
     * Gets the adminRegisterDocumentNotificationBody
     *
     * @return string
     */
    public function getAdminRegisterDocumentNotificationBody()
    {
        return $this->adminRegisterDocumentNotificationBody;
    }

    /**
     * Sets the adminRegisterDocumentNotificationBody
     *
     * @var string $adminRegisterDocumentNotificationBody
     * @return void
     */
    public function setAdminRegisterDocumentNotificationBody($adminRegisterDocumentNotificationBody)
    {
        $this->adminRegisterDocumentNotificationBody = $adminRegisterDocumentNotificationBody;
    }

}
