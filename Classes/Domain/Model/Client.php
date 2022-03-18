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
     * @var int
     */
    public static $storagePid = 0;

    /**
     * project
     *
     * @var string
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected $project = '';

    /**
     * client
     *
     * @var string
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected $client = '';

    /**
     * ownerId
     *
     * @var string
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected $ownerId = '';

    /**
     * networkInitial
     *
     * @var string
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected $networkInitial = '';

    /**
     * libraryIdentifier
     *
     * @var string
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
     */
    protected $libraryIdentifier = '';

    /**
     * adminEmail
     *
     * @var string
     * @TYPO3\CMS\Extbase\Annotation\Validate("NotEmpty")
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
     * fedoraEndpoint
     *
     * @var string
     */
    protected $fedoraEndpoint = '';

    /**
     * fedoraRootContainer
     *
     * @var string
     */
    protected $fedoraRootContainer = '';

    /**
     * fedoraCollectionNamespace
     *
     * @var string
     */
    protected $fedoraCollectionNamespace = '';

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
     * elasticSearchIndexName
     *
     * @var string
     */
    protected $elasticSearchIndexName = '';

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
     * @var string
     */
    protected $adminNewSuggestionSubject = '';

    /**
     * @var string
     */
    protected $adminNewSuggestionBody = '';

    /**
     * @var string
     */
    protected $adminEmbargoSubject = '';

    /**
     * @var string
     */
    protected $adminEmbargoBody = '';

    /**
     * adminDepositLicenseNotificationSubject
     *
     * @var string
     */
    protected $adminDepositLicenseNotificationSubject = '';

    /**
     * adminDepositLicenseNotificationBody
     *
     * @var string
     */
    protected $adminDepositLicenseNotificationBody = '';

    /**
     * @var bool
     */
    protected $sendAdminDepositLicenseNotification = false;

    /**
     * @var string
     */
    protected $suggestionFlashmessage = '';

    /**
     * fileXpath
     *
     * @var string
     */
    protected $fileXpath = '';

    /**
     * fileIdXpath
     *
     * @var string
     */
    protected $fileIdXpath = '';

    /**
     * fileMimetypeXpath
     *
     * @var string
     */
    protected $fileMimetypeXpath = '';

    /**
     * @var string
     */
    protected $fileHrefXpath = '';

    /**
     * @var string
     */
    protected $fileDownloadXpath = '';

    /**
     * @var string
     */
    protected $fileArchiveXpath = '';

    /**
     * @var string
     */
    protected $fileDeletedXpath = '';

    /**
     * @var string
     */
    protected $fileTitleXpath = '';

    /**
     * stateXpath
     *
     * @var string
     */
    protected $stateXpath = '';

    /**
     * typeXpath
     *
     * @var string
     */
    protected $typeXpath = '';

    /**
     * typeXpathInput
     *
     * @var string
     */
    protected $typeXpathInput = '';

    /**
     * dateXpath
     *
     * @var string
     */
    protected $dateXpath = '';

    /**
     * publishingYearXpath
     *
     * @var string
     */
    protected $publishingYearXpath = '';

    /**
     * urnXpath
     *
     * @var string
     */
    protected $urnXpath = '';

    /**
     * primaryUrnXpath
     *
     * @var string
     */
    protected $primaryUrnXpath = '';

    /**
     * @var string
     */
    protected $validationXpath = '';

    /**
     * @var string
     */
    protected $fisIdXpath = '';

    /**
     * namespaces
     *
     * @var string
     */
    protected $namespaces = '';

    /**
     * title xpath
     *
     * @var string
     */
    protected $titleXpath = '';

    /**
     * process number xpath
     *
     * @var string
     */
    protected $processNumberXpath = '';

    /**
     * submitter name
     *
     * @var string
     */
    protected $submitterNameXpath = '';

    /**
     * submitter email
     *
     * @var string
     */
    protected $submitterEmailXpath = '';

    /**
     * submitter notice
     *
     * @var string
     */
    protected $submitterNoticeXpath = '';

    /**
     * original source title xpath
     *
     * @var string
     */
    protected $originalSourceTitleXpath = '';

    /**
     * creator xpath
     *
     * @var string
     */
    protected $creatorXpath = '';

    /**
     * creation date xpath
     *
     * @var string
     */
    protected $creationDateXpath = '';

    /**
     * repository creation date xpath
     * @var string
     */
    protected $repositoryCreationDateXpath = '';

    /**
     * repository last mod date xpath
     *
     * @var string
     */
    protected $repositoryLastModDateXpath = '';

    /**
     * deposit license xpath
     * @var string
     */
    protected $depositLicenseXpath = '';

    /**
     * All notes Xpath
     *
     * @var string
     */
    protected $allNotesXpath = '';

    /**
     * Private notes Xpath
     *
     * @var string
     */
    protected $privateNotesXpath = '';

    /**
     * Person Xpath
     *
     * @var string
     */
    protected $personXpath  = '';

    /**
     * Person family Xpath
     *
     * @var string
     */
    protected $personFamilyXpath  = '';

    /**
     * Person given xpath
     *
     * @var string
     */
    protected $personGivenXpath  = '';

    /**
     * Person role xpath
     *
     * @var string
     */
    protected $personRoleXpath  = '';

    /**
     * Person fis identifier xpath
     *
     * @var string
     */
    protected $personFisIdentifierXpath  = '';

    /**
     * Person affiliation xpath
     *
     * @var string
     */
    protected $personAffiliationXpath  = '';

    /**
     * Person affiliation identifier xpath
     *
     * @var string
     */
    protected $personAffiliationIdentifierXpath  = '';

    /**
     * Source details xpaths (Semicolon separated)
     *
     * @var string
     */
    protected $sourceDetailsXpaths = '';

    /**
     * Person author role
     *
     * @var string
     */
    protected $personAuthorRole = '';

    /**
     * Person publisher role
     *
     * @var string
     */
    protected $personPublisherRole = '';

    /**
     * $mypublicationsUpdateNotificationSubject
     *
     * @var string
     */
    protected $mypublicationsUpdateNotificationSubject = '';

    /**
     * $mypublicationsUpdateNotificationBody
     *
     * @var string
     */
    protected $mypublicationsUpdateNotificationBody = '';

    /**
     * $mypublicationsNewNotificationSubject
     *
     * @var string
     */
    protected $mypublicationsNewNotificationSubject = '';

    /**
     * $mypublicationsNewNotificationBody
     *
     * @var string
     */
    protected $mypublicationsNewNotificationBody = '';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\TransformationFile>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $crossrefTransformation = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\TransformationFile>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $dataciteTransformation = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\TransformationFile>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $k10plusTransformation = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\TransformationFile>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $pubmedTransformation = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\TransformationFile>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $bibtexTransformation = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\TransformationFile>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $riswosTransformation = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\TransformationFile>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $inputTransformation = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\TransformationFile>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $outputTransformation = null;

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\TransformationFile>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $elasticSearchTransformation = null;

    /**
     * @var string
     */
    protected $activeMessagingSuggestionAcceptUrl = '';

    /**
     * @var string
     */
    protected $activeMessagingSuggestionDeclineUrl = '';

    /**
     * @var string
     */
    protected $activeMessagingNewDocumentUrl = '';

    /**
     * @var string
     */
    protected $activeMessagingChangedDocumentUrl = '';

    /**
     * @var string
     */
    protected $activeMessagingSuggestionAcceptUrlBody = '';

    /**
     * @var string
     */
    protected $activeMessagingSuggestionDeclineUrlBody = '';

    /**
     * @var string
     */
    protected $activeMessagingNewDocumentUrlBody = '';

    /**
     * @var string
     */
    protected $activeMessagingChangedDocumentUrlBody = '';

    /**
     * @var string
     */
    protected $fisMapping = '';

    /**
     * Collection xpath
     *
     * @var string
     */
    protected $collectionXpath  = '';

    /**
     * Fis collections
     *
     * @var string
     */
    protected $fisCollections  = '';

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
        $this->replaceNissPart = boolval($replaceNissPart);
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
     * @return string
     */
    public function getFedoraEndpoint(): string
    {
        return $this->fedoraEndpoint;
    }

    /**
     * @param string $fedoraEndpoint
     */
    public function setFedoraEndpoint(string $fedoraEndpoint): void
    {
        $this->fedoraEndpoint = $fedoraEndpoint;
    }

    /**
     * @return string
     */
    public function getFedoraRootContainer(): string
    {
        return $this->fedoraRootContainer;
    }

    /**
     * @param string $fedoraRootContainer
     */
    public function setFedoraRootContainer(string $fedoraRootContainer): void
    {
        $this->fedoraRootContainer = $fedoraRootContainer;
    }

    /**
     * @return string
     */
    public function getFedoraCollectionNamespace(): string
    {
        return $this->fedoraCollectionNamespace;
    }

    /**
     * @param string $fedoraCollectionNamespace
     */
    public function setFedoraCollectionNamespace(string $fedoraCollectionNamespace): void
    {
        $this->fedoraCollectionNamespace = $fedoraCollectionNamespace;
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
     * @return string
     */
    public function getElasticSearchIndexName(): string
    {
        return $this->elasticSearchIndexName;
    }

    /**
     * @param string $elasticSearchIndexName
     */
    public function setElasticSearchIndexName(string $elasticSearchIndexName): void
    {
        $this->elasticSearchIndexName = $elasticSearchIndexName;
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

    /**
     * @return string
     */
    public function getAdminNewSuggestionSubject(): string
    {
        return $this->adminNewSuggestionSubject;
    }

    /**
     * @param string $adminNewSuggestionSubject
     */
    public function setAdminNewSuggestionSubject(string $adminNewSuggestionSubject)
    {
        $this->adminNewSuggestionSubject = $adminNewSuggestionSubject;
    }

    /**
     * @return string
     */
    public function getAdminNewSuggestionBody(): string
    {
        return $this->adminNewSuggestionBody;
    }

    /**
     * @param string $adminNewSuggestionBody
     */
    public function setAdminNewSuggestionBody(string $adminNewSuggestionBody)
    {
        $this->adminNewSuggestionBody = $adminNewSuggestionBody;
    }

    /**
     * @return string
     */
    public function getAdminEmbargoSubject(): string
    {
        return $this->adminEmbargoSubject;
    }

    /**
     * @param string $adminEmbargoSubject
     */
    public function setAdminEmbargoSubject(string $adminEmbargoSubject)
    {
        $this->adminEmbargoSubject = $adminEmbargoSubject;
    }

    /**
     * @return string
     */
    public function getAdminEmbargoBody(): string
    {
        return $this->adminEmbargoBody;
    }

    /**
     * @param string $adminEmbargoBody
     */
    public function setAdminEmbargoBody(string $adminEmbargoBody)
    {
        $this->adminEmbargoBody = $adminEmbargoBody;
    }

    /**
     * @return string
     */
    public function getSuggestionFlashmessage(): string
    {
        return $this->suggestionFlashmessage;
    }

    /**
     * @param string $suggestionFlashmessage
     */
    public function setSuggestionFlashmessage(string $suggestionFlashmessage)
    {
        $this->suggestionFlashmessage = $suggestionFlashmessage;
    }

    /**
     * @return string
     */
    public function getMypublicationsUpdateNotificationSubject(): string
    {
        return $this->mypublicationsUpdateNotificationSubject;
    }

    /**
     * @param string $mypublicationsUpdateNotificationSubject
     */
    public function setMypublicationsUpdateNotificationSubject(string $mypublicationsUpdateNotificationSubject): void
    {
        $this->mypublicationsUpdateNotificationSubject = $mypublicationsUpdateNotificationSubject;
    }

    /**
     * @return string
     */
    public function getMypublicationsUpdateNotificationBody(): string
    {
        return $this->mypublicationsUpdateNotificationBody;
    }

    /**
     * @param string $mypublicationsUpdateNotificationBody
     */
    public function setMypublicationsUpdateNotificationBody(string $mypublicationsUpdateNotificationBody): void
    {
        $this->mypublicationsUpdateNotificationBody = $mypublicationsUpdateNotificationBody;
    }

    /**
     * @return string
     */
    public function getMypublicationsNewNotificationSubject(): string
    {
        return $this->mypublicationsNewNotificationSubject;
    }

    /**
     * @param string $mypublicationsNewNotificationSubject
     */
    public function setMypublicationsNewNotificationSubject(string $mypublicationsNewNotificationSubject): void
    {
        $this->mypublicationsNewNotificationSubject = $mypublicationsNewNotificationSubject;
    }

    /**
     * @return string
     */
    public function getMypublicationsNewNotificationBody(): string
    {
        return $this->mypublicationsNewNotificationBody;
    }

    /**
     * @param string $mypublicationsNewNotificationBody
     */
    public function setMypublicationsNewNotificationBody(string $mypublicationsNewNotificationBody): void
    {
        $this->mypublicationsNewNotificationBody = $mypublicationsNewNotificationBody;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getCrossrefTransformation(): \TYPO3\CMS\Extbase\Persistence\ObjectStorage
    {
        return $this->crossrefTransformation;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getDataciteTransformation(): \TYPO3\CMS\Extbase\Persistence\ObjectStorage
    {
        return $this->dataciteTransformation;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getK10plusTransformation(): \TYPO3\CMS\Extbase\Persistence\ObjectStorage
    {
        return $this->k10plusTransformation;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getPubmedTransformation(): \TYPO3\CMS\Extbase\Persistence\ObjectStorage
    {
        return $this->pubmedTransformation;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getBibtexTransformation(): \TYPO3\CMS\Extbase\Persistence\ObjectStorage
    {
        return $this->bibtexTransformation;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getRiswosTransformation(): \TYPO3\CMS\Extbase\Persistence\ObjectStorage
    {
        return $this->riswosTransformation;
    }
    /**
     * @return string
     */
    public function getAdminDepositLicenseNotificationSubject(): string
    {
        return $this->adminDepositLicenseNotificationSubject;
    }

    /**
     * @param string $adminDepositLicenseNotificationSubject
     */
    public function setAdminDepositLicenseNotificationSubject(string $adminDepositLicenseNotificationSubject): void
    {
        $this->adminDepositLicenseNotificationSubject = $adminDepositLicenseNotificationSubject;
    }

    /**
     * @return string
     */
    public function getAdminDepositLicenseNotificationBody(): string
    {
        return $this->adminDepositLicenseNotificationBody;
    }

    /**
     * @param string $adminDepositLicenseNotificationBody
     */
    public function setAdminDepositLicenseNotificationBody(string $adminDepositLicenseNotificationBody): void
    {
        $this->adminDepositLicenseNotificationBody = $adminDepositLicenseNotificationBody;
    }

    /**
     * @return bool
     */
    public function isSendAdminDepositLicenseNotification(): bool
    {
        return $this->sendAdminDepositLicenseNotification;
    }

    /**
     * @param bool $sendAdminDepositLicenseNotification
     */
    public function setSendAdminDepositLicenseNotification(bool $sendAdminDepositLicenseNotification): void
    {
        $this->sendAdminDepositLicenseNotification = boolval($sendAdminDepositLicenseNotification);
    }
    /**
     * @return string
     */
    public function getActiveMessagingSuggestionAcceptUrl(): string
    {
        return $this->activeMessagingSuggestionAcceptUrl;
    }

    /**
     * @param string $activeMessagingSuggestionAcceptUrl
     */
    public function setActiveMessagingSuggestionAcceptUrl(string $activeMessagingSuggestionAcceptUrl): void
    {
        $this->activeMessagingSuggestionAcceptUrl = $activeMessagingSuggestionAcceptUrl;
    }

    /**
     * @return string
     */
    public function getActiveMessagingSuggestionDeclineUrl(): string
    {
        return $this->activeMessagingSuggestionDeclineUrl;
    }

    /**
     * @param string $activeMessagingSuggestionDeclineUrl
     */
    public function setActiveMessagingSuggestionDeclineUrl(string $activeMessagingSuggestionDeclineUrl): void
    {
        $this->activeMessagingSuggestionDeclineUrl = $activeMessagingSuggestionDeclineUrl;
    }

    /**
     * @return string
     */
    public function getActiveMessagingNewDocumentUrl(): string
    {
        return $this->activeMessagingNewDocumentUrl;
    }

    /**
     * @param string $activeMessagingNewDocumentUrl
     */
    public function setActiveMessagingNewDocumentUrl(string $activeMessagingNewDocumentUrl): void
    {
        $this->activeMessagingNewDocumentUrl = $activeMessagingNewDocumentUrl;
    }

    /**
     * @return string
     */
    public function getActiveMessagingChangedDocumentUrl(): string
    {
        return $this->activeMessagingChangedDocumentUrl;
    }

    /**
     * @param string $activeMessagingChangedDocumentUrl
     */
    public function setActiveMessagingChangedDocumentUrl(string $activeMessagingChangedDocumentUrl): void
    {
        $this->activeMessagingChangedDocumentUrl = $activeMessagingChangedDocumentUrl;
    }

    /**
     * @return string
     */
    public function getActiveMessagingSuggestionAcceptUrlBody(): string
    {
        return $this->activeMessagingSuggestionAcceptUrlBody;
    }

    /**
     * @param string $activeMessagingSuggestionAcceptUrlBody
     */
    public function setActiveMessagingSuggestionAcceptUrlBody(string $activeMessagingSuggestionAcceptUrlBody): void
    {
        $this->activeMessagingSuggestionAcceptUrlBody = $activeMessagingSuggestionAcceptUrlBody;
    }

    /**
     * @return string
     */
    public function getActiveMessagingSuggestionDeclineUrlBody(): string
    {
        return $this->activeMessagingSuggestionDeclineUrlBody;
    }

    /**
     * @param string $activeMessagingSuggestionDeclineUrlBody
     */
    public function setActiveMessagingSuggestionDeclineUrlBody(string $activeMessagingSuggestionDeclineUrlBody): void
    {
        $this->activeMessagingSuggestionDeclineUrlBody = $activeMessagingSuggestionDeclineUrlBody;
    }

    /**
     * @return string
     */
    public function getActiveMessagingNewDocumentUrlBody(): string
    {
        return $this->activeMessagingNewDocumentUrlBody;
    }

    /**
     * @param string $activeMessagingNewDocumentUrlBody
     */
    public function setActiveMessagingNewDocumentUrlBody(string $activeMessagingNewDocumentUrlBody): void
    {
        $this->activeMessagingNewDocumentUrlBody = $activeMessagingNewDocumentUrlBody;
    }

    /**
     * @return string
     */
    public function getActiveMessagingChangedDocumentUrlBody(): string
    {
        return $this->activeMessagingChangedDocumentUrlBody;
    }

    /**
     * @param string $activeMessagingChangedDocumentUrlBody
     */
    public function setActiveMessagingChangedDocumentUrlBody(string $activeMessagingChangedDocumentUrlBody): void
    {
        $this->activeMessagingChangedDocumentUrlBody = $activeMessagingChangedDocumentUrlBody;
    }

    /**
     * @return string
     */
    public function getFisMapping(): string
    {
        return $this->fisMapping;
    }

    /**
     * @param string $fisMapping
     */
    public function setFisMapping(string $fisMapping): void
    {
        $this->fisMapping = $fisMapping;
    }

    /**
     * @return string
     */
    public function getFileXpath(): ?string
    {
        return $this->fileXpath;
    }

    /**
     * @param string $fileXpath
     */
    public function setFileXpath(string $fileXpath)
    {
        $this->fileXpath = $fileXpath;
    }

    /**
     * @return string
     */
    public function getStateXpath(): string
    {
        return $this->stateXpath;
    }

    /**
     * @param string $stateXpath
     */
    public function setStateXpath(string $stateXpath)
    {
        $this->stateXpath = $stateXpath;
    }

    /**
     * @return string
     */
    public function getTypeXpath(): string
    {
        return $this->typeXpath;
    }

    /**
     * @param string $typeXpath
     */
    public function setTypeXpath(string $typeXpath)
    {
        $this->typeXpath = $typeXpath;
    }

    /**
     * @return string
     */
    public function getTypeXpathInput(): string
    {
        return $this->typeXpathInput;
    }

    /**
     * @param string $typeXpathInput
     */
    public function setTypeXpathInput(string $typeXpathInput)
    {
        $this->typeXpathInput = $typeXpathInput;
    }

    /**
     * @return string
     */
    public function getDateXpath(): string
    {
        return $this->dateXpath;
    }

    /**
     * @param string $dateXpath
     */
    public function setDateXpath(string $dateXpath)
    {
        $this->dateXpath = $dateXpath;
    }

    /**
     * @return string
     */
    public function getUrnXpath(): string
    {
        return $this->urnXpath;
    }

    /**
     * @param string $urnXpath
     */
    public function setUrnXpath(string $urnXpath)
    {
        $this->urnXpath = $urnXpath;
    }

    /**
     * @return string
     */
    public function getNamespaces(): string
    {
        return $this->namespaces;
    }

    /**
     * @param string $namespaces
     */
    public function setNamespaces(string $namespaces)
    {
        $this->namespaces = $namespaces;
    }

    /**
     * @return string
     */
    public function getTitleXpath(): string
    {
        return $this->titleXpath;
    }

    /**
     * @param string $titleXpath
     */
    public function setTitleXpath(string $titleXpath)
    {
        $this->titleXpath = $titleXpath;
    }

    /**
     * @return string
     */
    public function getProcessNumberXpath(): string
    {
        return $this->processNumberXpath;
    }

    /**
     * @param string $processNumberXpath
     */
    public function setProcessNumberXpath(string $processNumberXpath)
    {
        $this->processNumberXpath = $processNumberXpath;
    }

    /**
     * @return string
     */
    public function getSubmitterNameXpath(): string
    {
        return $this->submitterNameXpath;
    }

    /**
     * @param string $submitterNameXpath
     */
    public function setSubmitterNameXpath(string $submitterNameXpath)
    {
        $this->submitterNameXpath = $submitterNameXpath;
    }

    /**
     * @return string
     */
    public function getSubmitterEmailXpath(): string
    {
        return $this->submitterEmailXpath;
    }

    /**
     * @param string $submitterEmailXpath
     */
    public function setSubmitterEmailXpath(string $submitterEmailXpath)
    {
        $this->submitterEmailXpath = $submitterEmailXpath;
    }

    /**
     * @return string
     */
    public function getSubmitterNoticeXpath(): string
    {
        return $this->submitterNoticeXpath;
    }

    /**
     * @param string $submitterNoticeXpath
     */
    public function setSubmitterNoticeXpath(string $submitterNoticeXpath)
    {
        $this->submitterNoticeXpath = $submitterNoticeXpath;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getInputTransformation(): \TYPO3\CMS\Extbase\Persistence\ObjectStorage
    {
        return $this->inputTransformation;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $inputTransformation
     */
    public function setInputTransformation(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $inputTransformation)
    {
        $this->inputTransformation = $inputTransformation;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getOutputTransformation(): \TYPO3\CMS\Extbase\Persistence\ObjectStorage
    {
        return $this->outputTransformation;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $outputTransformation
     */
    public function setOutputTransformation(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $outputTransformation)
    {
        $this->outputTransformation = $outputTransformation;
    }

    /**
     * @return string
     */
    public function getPrimaryUrnXpath(): string
    {
        return $this->primaryUrnXpath;
    }

    /**
     * @param string $primaryUrnXpath
     */
    public function setPrimaryUrnXpath(string $primaryUrnXpath)
    {
        $this->primaryUrnXpath = $primaryUrnXpath;
    }

    /**
     * @return string
     */
    public function getPublishingYearXpath(): string
    {
        return $this->publishingYearXpath;
    }

    /**
     * @param string $publishingYearXpath
     */
    public function setPublishingYearXpath(string $publishingYearXpath)
    {
        $this->publishingYearXpath = $publishingYearXpath;
    }

    /**
     * @return string
     */
    public function getOriginalSourceTitleXpath(): string
    {
        return $this->originalSourceTitleXpath;
    }

    /**
     * @param string $originalSourceTitleXpath
     */
    public function setOriginalSourceTitleXpath(string $originalSourceTitleXpath)
    {
        $this->originalSourceTitleXpath = $originalSourceTitleXpath;
    }

    /**
     * @return string
     */
    public function getCreatorXpath(): string
    {
        return $this->creatorXpath;
    }

    /**
     * @param string $creatorXpath
     */
    public function setCreatorXpath(string $creatorXpath)
    {
        $this->creatorXpath = $creatorXpath;
    }

    /**
     * @return string
     */
    public function getCreationDateXpath(): string
    {
        return $this->creationDateXpath;
    }

    /**
     * @param string $creationDateXpath
     */
    public function setCreationDateXpath(string $creationDateXpath)
    {
        $this->creationDateXpath = $creationDateXpath;
    }

    /**
     * @return string
     */
    public function getRepositoryCreationDateXpath(): string
    {
        return $this->repositoryCreationDateXpath;
    }

    /**
     * @param string $repositoryCreationDateXpath
     */
    public function setRepositoryCreationDateXpath(string $repositoryCreationDateXpath)
    {
        $this->repositoryCreationDateXpath = $repositoryCreationDateXpath;
    }

    /**
     * @return string
     */
    public function getRepositoryLastModDateXpath(): string
    {
        return $this->repositoryLastModDateXpath;
    }

    /**
     * @param string $repositoryLastModDateXpath
     */
    public function setRepositoryLastModDateXpath(string $repositoryLastModDateXpath)
    {
        $this->repositoryLastModDateXpath = $repositoryLastModDateXpath;
    }

    /**
     * @return string
     */
    public function getDepositLicenseXpath(): string
    {
        return $this->depositLicenseXpath;
    }

    /**
     * @param string $depositLicenseXpath
     */
    public function setDepositLicenseXpath(string $depositLicenseXpath)
    {
        $this->depositLicenseXpath = $depositLicenseXpath;
    }

    /**
     * @return string
     */
    public function getAllNotesXpath(): string
    {
        return $this->allNotesXpath;
    }

    /**
     * @param string $allNotesXpath
     */
    public function setAllNotesXpath(string $allNotesXpath)
    {
        $this->allNotesXpath = $allNotesXpath;
    }

    /**
     * @return string
     */
    public function getPrivateNotesXpath(): string
    {
        return $this->privateNotesXpath;
    }

    /**
     * @param string $privateNotesXpath
     */
    public function setPrivateNotesXpath(string $privateNotesXpath)
    {
        $this->privateNotesXpath = $privateNotesXpath;
    }

    /**
     * @return string
     */
    public function getPersonXpath(): string
    {
        return $this->personXpath;
    }

    /**
     * @param string $personXpath
     */
    public function setPersonXpath(string $personXpath)
    {
        $this->personXpath = $personXpath;
    }

    /**
     * @return string
     */
    public function getPersonFamilyXpath(): string
    {
        return $this->personFamilyXpath;
    }

    /**
     * @param string $personFamilyXpath
     */
    public function setPersonFamilyXpath(string $personFamilyXpath)
    {
        $this->personFamilyXpath = $personFamilyXpath;
    }

    /**
     * @return string
     */
    public function getPersonGivenXpath(): string
    {
        return $this->personGivenXpath;
    }

    /**
     * @param string $personGivenXpath
     */
    public function setPersonGivenXpath(string $personGivenXpath)
    {
        $this->personGivenXpath = $personGivenXpath;
    }

    /**
     * @return string
     */
    public function getPersonRoleXpath(): string
    {
        return $this->personRoleXpath;
    }

    /**
     * @param string $personRoleXpath
     */
    public function setPersonRoleXpath(string $personRoleXpath)
    {
        $this->personRoleXpath = $personRoleXpath;
    }

    /**
     * @return string
     */
    public function getPersonFisIdentifierXpath(): string
    {
        return $this->personFisIdentifierXpath;
    }

    /**
     * @param string $personFisIdentifierXpath
     */
    public function setPersonFisIdentifierXpath(string $personFisIdentifierXpath)
    {
        $this->personFisIdentifierXpath = $personFisIdentifierXpath;
    }

    /**
     * @return string
     */
    public function getPersonAffiliationXpath(): string
    {
        return $this->personAffiliationXpath;
    }

    /**
     * @param string $personAffiliationXpath
     */
    public function setPersonAffiliationXpath(string $personAffiliationXpath)
    {
        $this->personAffiliationXpath = $personAffiliationXpath;
    }

    /**
     * @return string
     */
    public function getPersonAffiliationIdentifierXpath(): string
    {
        return $this->personAffiliationIdentifierXpath;
    }

    /**
     * @param string $personAffiliationIdentifierXpath
     */
    public function setPersonAffiliationIdentifierXpath(string $personAffiliationIdentifierXpath)
    {
        $this->personAffiliationIdentifierXpath = $personAffiliationIdentifierXpath;
    }

    /**
     * @return string
     */
    public function getPersonAuthorRole(): string
    {
        return $this->personAuthorRole;
    }

    /**
     * @param string $personAuthorRole
     */
    public function setPersonAuthorRole(string $personAuthorRole)
    {
        $this->personAuthorRole = $personAuthorRole;
    }

    /**
     * @return string
     */
    public function getPersonPublisherRole(): string
    {
        return $this->personPublisherRole;
    }

    /**
     * @param string $personPublisherRole
     */
    public function setPersonPublisherRole(string $personPublisherRole)
    {
        $this->personPublisherRole = $personPublisherRole;
    }

    /**
     * @return string
     */
    public function getValidationXpath(): string
    {
        return $this->validationXpath;
    }

    /**
     * @param string $validationXpath
     */
    public function setValidationXpath(string $validationXpath)
    {
        $this->validationXpath = $validationXpath;
    }

    /**
     * @return string
     */
    public function getFisIdXpath(): string
    {
        return $this->fisIdXpath;
    }

    /**
     * @param string $fisIdXpath
     */
    public function setFisIdXpath(string $fisIdXpath)
    {
        $this->fisIdXpath = $fisIdXpath;
    }

    /**
     * @return string
     */
    public function getSourceDetailsXpaths(): string
    {
        return $this->sourceDetailsXpaths;
    }

    /**
     * @param string $sourceDetailsXpaths
     */
    public function setSourceDetailsXpaths(string $sourceDetailsXpaths)
    {
        $this->sourceDetailsXpaths = $sourceDetailsXpaths;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getElasticSearchTransformation(): ?\TYPO3\CMS\Extbase\Persistence\ObjectStorage
    {
        return $this->elasticSearchTransformation;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $elasticSearchTransformation
     */
    public function setElasticSearchTransformation(
        ?\TYPO3\CMS\Extbase\Persistence\ObjectStorage $elasticSearchTransformation
    ): void {
        $this->elasticSearchTransformation = $elasticSearchTransformation;
    }

    /**
     * @return string
     */
    public function getFileIdXpath(): ?string
    {
        return $this->fileIdXpath;
    }

    /**
     * @param string $fileIdXpath
     */
    public function setFileIdXpath(string $fileIdXpath): void
    {
        $this->fileIdXpath = $fileIdXpath;
    }

    /**
     * @return string
     */
    public function getFileMimetypeXpath(): ?string
    {
        return $this->fileMimetypeXpath;
    }

    /**
     * @param string $fileMimetypeXpath
     */
    public function setFileMimetypeXpath(string $fileMimetypeXpath): void
    {
        $this->fileMimetypeXpath = $fileMimetypeXpath;
    }

    /**
     * @return string
     */
    public function getFileHrefXpath(): string
    {
        return $this->fileHrefXpath;
    }

    /**
     * @param string $fileHrefXpath
     */
    public function setFileHrefXpath(string $fileHrefXpath): void
    {
        $this->fileHrefXpath = $fileHrefXpath;
    }

    /**
     * @return string
     */
    public function getFileDownloadXpath(): string
    {
        return $this->fileDownloadXpath;
    }

    /**
     * @param string $fileDownloadXpath
     */
    public function setFileDownloadXpath(string $fileDownloadXpath): void
    {
        $this->fileDownloadXpath = $fileDownloadXpath;
    }

    /**
     * @return string
     */
    public function getFileArchiveXpath(): string
    {
        return $this->fileArchiveXpath;
    }

    /**
     * @param string $fileArchiveXpath
     */
    public function setFileArchiveXpath(string $fileArchiveXpath): void
    {
        $this->fileArchiveXpath = $fileArchiveXpath;
    }

    /**
     * @return string
     */
    public function getFileDeletedXpath(): string
    {
        return $this->fileDeletedXpath;
    }

    /**
     * @param string $fileDeletedXpath
     */
    public function setFileDeletedXpath(string $fileDeletedXpath): void
    {
        $this->fileDeletedXpath = $fileDeletedXpath;
    }

    /**
     * @return string
     */
    public function getFileTitleXpath(): string
    {
        return $this->fileTitleXpath;
    }

    /**
     * @param string $fileTitleXpath
     */
    public function setFileTitleXpath(string $fileTitleXpath): void
    {
        $this->fileTitleXpath = $fileTitleXpath;
    }

    /**
     * @return string
     */
    public function getCollectionXpath(): string
    {
        return $this->collectionXpath;
    }

    /**
     * @param string $collectionXpath
     */
    public function setCollectionXpath(string $collectionXpath): void
    {
        $this->collectionXpath = $collectionXpath;
    }

    /**
     * @return string
     */
    public function getFisCollections(): string
    {
        return $this->fisCollections;
    }

    /**
     * @param string $fisCollections
     */
    public function setFisCollections(string $fisCollections): void
    {
        $this->fisCollections = $fisCollections;
    }
}
