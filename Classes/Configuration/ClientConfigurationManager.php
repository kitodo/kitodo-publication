<?php /** @noinspection ALL */

namespace EWW\Dpf\Configuration;

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

use EWW\Dpf\Domain\Repository\ClientRepository;
use Exception;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ClientConfigurationManager implements SingletonInterface
{
    // FIXME Make Fedora https connection scheme configurable

    /**
     * settings
     *
     * @var \EWW\Dpf\Domain\Model\Client
     */
    protected $client = null;

    /**
     * extensionConfiguration
     *
     * @var array
     */
    protected $extensionConfiguration = array();

    public function __construct()
    {
        // FIXME Backend setting retrieval removed in commit f3f178a47c9d7f22e086b49634cefa454f2a950f
        // This might have broken Backend handover module
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $configurationManager = $objectManager->get(ConfigurationManager::class);

        $this->extensionConfiguration =
            GeneralUtility::makeInstance(ExtensionConfiguration::class)
                ->get('dpf');

        // best-effort in finding the client configuration record
        // requires TYPOScript setup to specify storage PID
        $clientRepository = $objectManager->get(ClientRepository::class);

        // Assuming that storagePID for ClientRepository is set correctly
        // get the first available client record
        $this->client = $clientRepository->findAll()->getFirst();

        // FIXME Deprecated mode check
        if (TYPO3_MODE == "FE" && !$this->client) {
            throw new Exception("Cannot obtain client record although in frontend mode. Is the storage PID set?");
        }
    }

    /**
     * Switch to specific client configuration
     *
     * Sort of hack to select client records from backend module
     * since TYPOScript storagePid configuration is not avalable in
     * TYPO3 backend.
     *
     * @param int $uid Switch to client record with given UID
     */
    public function switchToClient($uid)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $clientRepository = $objectManager->get(ClientRepository::class);
        $client = $clientRepository->findByUid($uid);
        if (!$client) {
            throw new Exception("Unable to find client with UID `{$uid}`");
        }
        $this->client = $client;
    }

    /**
     * Switch to first client configuration given a storage PID
     *
     * Sort of hack to select client records from backend module
     * since TYPOScript storagePid configuration is not avalable in
     * TYPO3 backend.
     *
     * @param int $pid Storage PID of client records
     */
    public function switchToClientStorage($pid)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $clientRepository = $objectManager->get(ClientRepository::class);
        $client = $clientRepository->findAllByPid($pid)->getFirst();
        if (!$client) {
            throw new Exception("Unable to find client record in storage with PID `{$pid}`");
        }
        $this->client = $client;
    }


    /**
     * @return int|null
     */
    public function getClientPid()
    {
        return $this->client->getPid();
    }

    /**
     * Get setting from client or extension configuration.
     *
     * @var array
     */
    public function getSetting($settingName)
    {
        $setting = null;
        if ($this->client) {
            $setting = trim($this->client->{"get" . ucfirst($settingName)}());
        }

        // use global extConfig if client settings is empty
        if (empty($setting)) {
            $setting = trim($this->extensionConfiguration[$settingName]);
        }

        return $setting;
    }


    public function getOwnerId()
    {
        return $this->getSetting("ownerId");
    }

    public function getFedoraHost()
    {
        return $this->getSetting("fedoraHost");
    }

    public function getFedoraUser()
    {
        return $this->getSetting("fedoraUser");
    }

    public function getFedoraPassword()
    {
        return $this->getSetting("fedoraPassword");
    }

    public function getFedoraEndpoint()
    {
        return $this->getSetting("fedoraEndpoint");
    }

    public function getFedoraRootContainer()
    {
        return $this->getSetting("fedoraRootContainer");
    }

    public function getFedraCollectionNamespace()
    {
        return $this->getSetting("fedoraCollectionNamespace");
    }

    public function getElasticSearchHost()
    {
        return $this->getSetting("elasticSearchHost");
    }

    public function getElasticSearchPort()
    {
        return $this->getSetting("elasticSearchPort");
    }

    public function getElasticSearchIndexName()
    {
        return $this->getSetting("elasticSearchIndexName");
    }

    public function getUploadDirectory()
    {
        return $this->getSetting("uploadDirectory");
    }

    public function getUploadDomain()
    {
        return $this->getSetting("uploadDomain");
    }

    public function getSuggestionFlashMessage()
    {
        return $this->getSetting("suggestionFlashmessage");
    }

    public function getFileXpath()
    {
        return $this->getSetting("fileXpath");
    }

    // TODO: deprecated
    public function getStateXpath()
    {
        return $this->getSetting("stateXpath");
    }

    public function getTypeXpath()
    {
        return $this->getSetting("typeXpath");
    }

    public function getTypeXpathInput()
    {
        return $this->getSetting("typeXpathInput");
    }

    public function getUrnXpath()
    {
        return $this->getSetting("urnXpath");
    }

    public function getPrimaryUrnXpath()
    {
        return $this->getSetting("primaryUrnXpath");
    }

    public function getDateXpath()
    {
        return $this->getSetting("dateXpath");
    }

    public function getSearchYearXpaths()
    {
        return $this->getSetting("searchYearXpaths");
    }

    public function getPublisherXpaths()
    {
        return $this->getSetting("publisherXpaths");
    }

    public function getAdditionalSearchTitleXpaths()
    {
        return $this->getSetting("additionalSearchTitleXpaths");
    }

    public function getAdditionalIdentifierXpaths()
    {
        return $this->getSetting("additionalIdentifierXpaths");
    }

    public function getSearchLanguageXpaths()
    {
        return $this->getSetting("searchLanguageXpaths");
    }

    public function getSearchCorporationXpaths()
    {
        return $this->getSetting("searchCorporationXpaths");
    }

    public function getNamespaces()
    {
        return $this->getSetting("namespaces");
    }

    public function getTitleXpath()
    {
        return $this->getSetting("titleXpath");
    }

    public function getOriginalSourceTitleXpath()
    {
        return $this->getSetting("originalSourceTitleXpath");
    }

    public function getProcessNumberXpath()
    {
        return $this->getSetting("processnumberXpath");
    }

    public function getSubmitterNameXpath()
    {
        return $this->getSetting("submitterNameXpath");
    }

    public function getSubmitterEmailXpath()
    {
        return $this->getSetting("submitterEmailXpath");
    }

    public function getSubmitterNoticeXpath()
    {
        return $this->getSetting("submitterNoticeXpath");
    }

    public function getCreatorXpath()
    {
        return $this->getSetting("creatorXpath");
    }

    public function getCreationDateXpath()
    {
        return $this->getSetting("creationDateXpath");
    }

    // TODO: deprecated
    public function getRepositoryCreationDateXpath()
    {
        return $this->getSetting("repositoryCreationDateXpath");
    }

    // TODO: deprecated
    public function getRepositoryLastModDateXpath()
    {
        return $this->getSetting("repositoryLastModDateXpath");
    }

    public function getDepositLicenseXpath()
    {
        return $this->getSetting("depositLicenseXpath");
    }

    public function getAllNotesXpath()
    {
        return $this->getSetting("allNotesXpath");
    }

    public function getPrivateNotesXpath()
    {
        return $this->getSetting("privateNotesXpath");
    }

    public function getInputTransformation()
    {
        return $this->client->getInputTransformation()->current();
    }

    public function getOutputTransformation()
    {
        return $this->client->getOutputTransformation()->current();
    }

    public function getPersonXpath()
    {
        return $this->getSetting("personXpath");
    }

    public function getPersonFamilyXpath()
    {
        return $this->getSetting("personFamilyXpath");
    }

    public function getPersonGivenXpath()
    {
        return $this->getSetting("personGivenXpath");
    }

    public function getPersonRoleXpath()
    {
        return $this->getSetting("personRoleXpath");
    }

    public function getPersonFisIdentifierXpath()
    {
        return $this->getSetting("personFisIdentifierXpath");
    }

    public function getPersonAffiliationXpath()
    {
        return $this->getSetting("personAffiliationXpath");
    }

    public function getPersonAuthorRole()
    {
        return $this->getSetting("personAuthorRole");
    }

    public function getPersonPublisherRole()
    {
        return $this->getSetting("personPublisherRole");
    }

    public function getFisIdXpath()
    {
        return $this->getSetting("fisIdXpath");
    }

    public function getSourceDetailsXpaths()
    {
        return $this->getSetting("sourceDetailsXpaths");
    }

    public function getFedoraNamespace()
    {
        $settings = $this->getTypoScriptSettings();
        return $settings['fedoraNamespace'];
    }

    public function getUniversityCollection()
    {
        $settings = $this->getTypoScriptSettings();
        return $settings['universityCollection'];
    }

    public function isAlwaysSetDateIssued()
    {
        $settings = $this->getTypoScriptSettings();
        return !empty($settings['activateAlwaysSetDateIssued']);
    }

    public function getPeerReviewValues()
    {
        $settings = $this->getTypoScriptSettings();
        return $settings['peerReviewValues'];
    }

    public function getOpenAccessValues()
    {
        $settings = $this->getTypoScriptSettings();
        return $settings['openAccessValues'];
    }

    public function getUnpaywallOAValues()
    {
        $settings = $this->getTypoScriptSettings();
        return $settings['unpaywallOAValues'];
    }

    public function getFisApiWorkflowStateName()
    {
        $settings = $this->getTypoScriptSettings();
        return $settings['fisApi']['workflowStateName'];
    }

    public function getTypoScriptSettings()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $configurationManager = $objectManager->get(ConfigurationManager::class);
        $settings = $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS
        );

        return $settings;
    }

    public function getFileIdXpath()
    {
        return $this->trimFileXpath($this->getSetting("fileIdXpath"));
    }

    public function getFilePrimaryXpath()
    {
        return $this->trimFileXpath($this->getSetting("filePrimaryXpath"));
    }

    public function getFileMimetypeXpath()
    {
        return $this->trimFileXpath($this->getSetting("fileMimetypeXpath"));
    }

    public function getFileHrefXpath()
    {
        return $this->trimFileXpath($this->getSetting("fileHrefXpath"));
    }

    public function getFileDownloadXpath()
    {
        return $this->trimFileXpath($this->getSetting("fileDownloadXpath"));
    }

    public function getFileArchiveXpath()
    {
        return $this->trimFileXpath($this->getSetting("fileArchiveXpath"));
    }

    public function getFileDeletedXpath()
    {
        return $this->trimFileXpath($this->getSetting("fileDeletedXpath"));
    }

    public function getFileTitleXpath()
    {
        return $this->trimFileXpath($this->getSetting("fileTitleXpath"));
    }

    public function getCollectionXpath()
    {
        return $this->trimFileXpath($this->getSetting("collectionXpath"));
    }

    public function getTextTypeXpath()
    {
        return $this->trimFileXpath($this->getSetting("textTypeXpath"));
    }

    public function getOpenAccessXpath()
    {
        return $this->trimFileXpath($this->getSetting("openAccessXpath"));
    }

    public function getPeerReviewXpath()
    {
        return $this->trimFileXpath($this->getSetting("peerReviewXpath"));
    }

    public function getPeerReviewOtherVersionXpath()
    {
        return $this->trimFileXpath($this->getSetting("peerReviewOtherVersionXpath"));
    }

    public function getLicenseXpath()
    {
        return $this->trimFileXpath($this->getSetting("licenseXpath"));
    }

    public function getProjectIdXpath()
    {
        return $this->trimFileXpath($this->getSetting("projectIdXpath"));
    }

    public function getProjectTitleXpath()
    {
        return $this->trimFileXpath($this->getSetting("projectTitleXpath"));
    }

    public function getFisCollections()
    {
        $fisCollectionsConfig =  $this->getSetting("fisCollections");
        $fisCollections = explode(",", $fisCollectionsConfig);
        return array_filter($fisCollections, 'strlen');
    }

    public function getNoReplyAddress()
    {
        $settings = $this->getTypoScriptSettings();

        if (isset($settings['noReplyAddress']) && $settings['noReplyAddress']) {
            return $settings['noReplyAddress'];
        }

        return trim($this->extensionConfiguration['noReplyAddress']);
    }

    /**
     * @param string $xpath
     * @return string
     */
    protected function trimFileXpath(string $xpath): ?string
    {
        return trim($xpath, "/ ");
    }

    public function getClient()
    {
        return $this->client;
    }
}
