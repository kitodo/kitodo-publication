<?php
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use EWW\Dpf\Domain\Model\Client;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use EWW\Dpf\Domain\Repository\ClientRepository;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;

class ClientConfigurationManager
{
    // FIXME Make Fedora https connection scheme configurable

    /**
     * settings
     *
     * @var array
     */
    protected $settings = array();


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
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $clientRepository = $objectManager->get(ClientRepository::class);

        if (TYPO3_MODE === 'BE') {
            $selectedPageId = (int)GeneralUtility::_GP('id');
            if ($selectedPageId) {
                $this->client = $clientRepository->findAll()->current();

                $configurationManager = $objectManager->get(BackendConfigurationManager::class);
                $settings = $configurationManager->getConfiguration(null, null);
                $this->settings = $settings; //['settings'];
            }

        } else {
            $this->client = $clientRepository->findAll()->current();

            $configurationManager = $objectManager->get(ConfigurationManager::class);
            $this->settings = $configurationManager->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS
            );

        }

        if (Client::$storagePid > 0) {
            $this->setConfigurationPid(Client::$storagePid);
        }

        $this->extensionConfiguration = GeneralUtility::makeInstance(
            ExtensionConfiguration::class
        )->get('dpf');

    }

    public function setConfigurationPid($pid)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $clientRepository = $objectManager->get(ClientRepository::class);

        $this->client = $clientRepository->findAllByPid($pid)->current();
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
    public function getSetting($settingName, $extConfig = null)
    {
        $setting = null;
        if ($this->client) {
            $setting = trim($this->client->{"get" . ucfirst($settingName)}());
        }

        // use global extConfig if client settings is empty
        if (empty($setting) && $extConfig) {
            $setting = trim($this->extensionConfiguration[$extConfig]);
        }

        return $setting;
    }


    public function getOwnerId()
    {
        return $this->getSetting("ownerId");
    }

    public function getFedoraHost()
    {
        return $this->getSetting("fedoraHost", "fedoraHost");
    }

    public function getFedoraUser()
    {
        return $this->getSetting("fedoraUser", "fedoraUser");
    }

    public function getFedoraPassword()
    {
        return $this->getSetting("fedoraPassword", "fedoraPassword");
    }

    public function getFedoraEndpoint()
    {
        return $this->getSetting("fedoraEndpoint", "fedoraEndpoint");
    }

    public function getFedoraRootContainer()
    {
        return $this->getSetting("fedoraRootContainer", "fedoraRootContainer");
    }

    public function getFedraCollectionNamespace()
    {
        return $this->getSetting("fedoraCollectionNamespace", "fedoraCollectionNamespace");
    }

    public function getElasticSearchHost()
    {
        return $this->getSetting("elasticSearchHost", "elasticSearchHost");
    }

    public function getElasticSearchPort()
    {
        return $this->getSetting("elasticSearchPort", "elasticSearchPort");
    }

    public function getElasticSearchIndexName()
    {
        return $this->getSetting("elasticSearchIndexName", "elasticSearchIndexName");
    }

    public function getUploadDirectory()
    {
        return $this->getSetting("uploadDirectory", "uploadDirectory");
    }

    public function getUploadDomain()
    {
        return $this->getSetting("uploadDomain", "uploadDomain");
    }

    public function getSuggestionFlashMessage()
    {
        return $this->getSetting("suggestionFlashmessage", "suggestionFlashmessage");
    }

    public function getFileXpath()
    {
        return $this->getSetting("fileXpath", "fileXpath");
    }

    // TODO: deprecated
    public function getStateXpath()
    {
        return $this->getSetting("stateXpath", "stateXpath");
    }

    public function getTypeXpath()
    {
        return $this->getSetting("typeXpath", "typeXpath");
    }

    public function getTypeXpathInput()
    {
        return $this->getSetting("typeXpathInput", "typeXpathInput");
    }

    public function getUrnXpath()
    {
        return $this->getSetting("urnXpath", "urnXpath");
    }

    public function getPrimaryUrnXpath()
    {
        return $this->getSetting("primaryUrnXpath", "primaryUrnXpath");
    }

    public function getDateXpath()
    {
        return $this->getSetting("dateXpath", "dateXpath");
    }

    public function getPublishingYearXpath()
    {
        return $this->getSetting("publishingYearXpath", "publishingYearXpath");
    }

    public function getNamespaces()
    {
        return $this->getSetting("namespaces", "namespaces");
    }

    public function getTitleXpath()
    {
        return $this->getSetting("titleXpath", "titleXpath");
    }

    public function getOriginalSourceTitleXpath()
    {
        return $this->getSetting("originalSourceTitleXpath", "originalSourceTitleXpath");
    }

    public function getProcessNumberXpath()
    {
        return $this->getSetting("processnumberXpath", "processnumberXpath");
    }

    public function getSubmitterNameXpath()
    {
        return $this->getSetting("submitterNameXpath", "submitterNameXpath");
    }

    public function getSubmitterEmailXpath()
    {
        return $this->getSetting("submitterEmailXpath", "submitterEmailXpath");
    }

    public function getSubmitterNoticeXpath()
    {
        return $this->getSetting("submitterNoticeXpath", "submitterNoticeXpath");
    }

    public function getCreatorXpath()
    {
        return $this->getSetting("creatorXpath", "creatorXpath");
    }

    public function getCreationDateXpath()
    {
        return $this->getSetting("creationDateXpath", "creationDateXpath");
    }

    // TODO: deprecated
    public function getRepositoryCreationDateXpath()
    {
        return $this->getSetting("repositoryCreationDateXpath", "repositoryCreationDateXpath");
    }

    // TODO: deprecated
    public function getRepositoryLastModDateXpath()
    {
        return $this->getSetting("repositoryLastModDateXpath", "repositoryLastModDateXpath");
    }

    public function getDepositLicenseXpath()
    {
        return $this->getSetting("depositLicenseXpath", "depositLicenseXpath");
    }

    public function getAllNotesXpath()
    {
        return $this->getSetting("allNotesXpath", "allNotesXpath");
    }

    public function getPrivateNotesXpath()
    {
        return $this->getSetting("privateNotesXpath", "privateNotesXpath");
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
        return $this->getSetting("personXpath", "personXpath");
    }

    public function getPersonFamilyXpath()
    {
        return $this->getSetting("personFamilyXpath", "personFamilyXpath");
    }

    public function getPersonGivenXpath()
    {
        return $this->getSetting("personGivenXpath", "personGivenXpath");
    }

    public function getPersonRoleXpath()
    {
        return $this->getSetting("personRoleXpath", "personRoleXpath");
    }

    public function getPersonFisIdentifierXpath()
    {
        return $this->getSetting("personFisIdentifierXpath", "personFisIdentifierXpath");
    }

    public function getPersonAffiliationXpath()
    {
        return $this->getSetting("personAffiliationXpath", "personAffiliationXpath");
    }

    public function getPersonAffiliationIdentifierXpath()
    {
        return $this->getSetting("personAffiliationIdentifierXpath", "personAffiliationIdentifierXpath");
    }

    public function getPersonAuthorRole()
    {
        return $this->getSetting("personAuthorRole", "personAuthorRole");
    }

    public function getPersonPublisherRole()
    {
        return $this->getSetting("personPublisherRole", "personPublisherRole");
    }

    public function getValidationXpath()
    {
        return $this->getSetting("validationXpath", "validationXpath");
    }

    public function getFisIdXpath()
    {
        return $this->getSetting("fisIdXpath", "fisIdXpath");
    }

    public function getSourceDetailsXpaths()
    {
        return $this->getSetting("sourceDetailsXpaths", "sourceDetailsXpaths");
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
        return $this->trimFileXpath($this->getSetting("fileIdXpath", "fileIdXpath"));
    }

    public function getFileMimetypeXpath()
    {
        return $this->trimFileXpath($this->getSetting("fileMimetypeXpath", "fileMimetypeXpath"));
    }

    public function getFileHrefXpath()
    {
        return $this->trimFileXpath($this->getSetting("fileHrefXpath", "fileHrefXpath"));
    }

    public function getFileDownloadXpath()
    {
        return $this->trimFileXpath($this->getSetting("fileDownloadXpath", "fileDownloadXpath"));
    }

    public function getFileArchiveXpath()
    {
        return $this->trimFileXpath($this->getSetting("fileArchiveXpath", "fileArchiveXpath"));
    }

    public function getFileDeletedXpath()
    {
        return$this->trimFileXpath($this->getSetting("fileDeletedXpath", "fileDeletedXpath"));
    }

    public function getFileTitleXpath()
    {
        return $this->trimFileXpath($this->getSetting("fileTitleXpath", "fileTitleXpath"));
    }

    public function getCollectionXpath()
    {
        return $this->trimFileXpath($this->getSetting("collectionXpath", "collectionXpath"));
    }

    public function getFisCollections()
    {
        $fisCollectionsConfig =  $this->getSetting("fisCollections", "fisCollections");
        $fisCollections = explode(",", $fisCollectionsConfig);
        return array_filter($fisCollections, 'strlen' );
    }

    /**
     * @param string $xpath
     * @return string
     */
    protected function trimFileXpath(string $xpath): ?string
    {
        return trim($xpath, "@/ ");
    }
}
