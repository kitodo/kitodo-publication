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

use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use EWW\Dpf\Domain\Repository\ClientRepository;

class ClientConfigurationManager
{

	/**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @inject
     */
    protected $objectManager;

    /**
     * clientRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ClientRepository
     * @inject
     */
    protected $clientRepository;

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
    protected $client = NULL;

    /**
     * extensionConfiguration
     *
     * @var array
     */
    protected $extensionConfiguration = array();

    public function __construct()
    {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $clientRepository = $objectManager->get(ClientRepository::class);

		if (TYPO3_MODE === 'BE')
		{
            $selectedPageId = (int) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');
            if ($selectedPageId)
            {
                $this->client = $clientRepository->findAll()->current();

                $configurationManager = $objectManager->get(BackendConfigurationManager::class);
                $settings = $configurationManager->getConfiguration(NULL,NULL);
                $this->settings = $settings; //['settings'];
            }

		}
		else
		{
            $this->client = $clientRepository->findAll()->current();

            $configurationManager = $objectManager->get(ConfigurationManager::class);
    		$this->settings = $configurationManager->getConfiguration(
            	\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS
        	);

        }

        $this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dpf']);
    }

    public function setConfigurationPid($pid) {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $clientRepository = $objectManager->get(ClientRepository::class);

        $this->client = $clientRepository->findAllByPid($pid)->current();
    }


    /**
     * Get setting from client or extension configuration.
     *
     * @var array
     */
    public function getSetting($settingName, $extConfig = NULL)
    {
        $setting = NULL;
        if ($this->client) {
            $setting = trim($this->client->{"get".ucfirst($settingName)}());
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

    public function getSwordHost()
    {
    	return $this->getSetting("swordHost","swordHost");
    }

	public function getSwordUser()
    {
    	return $this->getSetting("swordUser","swordUser");
    }

	public function getSwordPassword()
    {
    	return $this->getSetting("swordPassword","swordPassword");
    }

	public function getSwordCollectionNamespace()
    {
    	return $this->getSetting("swordCollectionNamespace","swordCollectionNamespace");
    }

	public function getFedoraHost()
    {
    	return $this->getSetting("fedoraHost","fedoraHost");
    }

	public function getFedoraUser()
    {
    	return $this->getSetting("fedoraUser","fedoraUser");
    }

	public function getFedoraPassword()
    {
    	return $this->getSetting("fedoraPassword","fedoraPassword");
    }

	public function getElasticSearchHost()
    {
    	return $this->getSetting("elasticSearchHost","elasticSearchHost");
    }

	public function getElasticSearchPort()
    {
    	return $this->getSetting("elasticSearchPort","elasticSearchPort");
    }

	public function getUploadDirectory()
    {
    	return $this->getSetting("uploadDirectory","uploadDirectory");
    }

	public function getUploadDomain()
    {
    	return $this->getSetting("uploadDomain","uploadDomain");
    }

    public function getSuggestionFlashMessage()
    {
        return $this->getSetting("suggestionFlashmessage", "suggestionFlashmessage");
    }

    public function getFileXpath()
    {
        return $this->getSetting("fileXpath","fileXpath");
    }

    public function getStateXpath()
    {
        return $this->getSetting("stateXpath","stateXpath");
    }

    public function getTypeXpath()
    {
        return $this->getSetting("typeXpath","typeXpath");
    }

    public function getTypeXpathInput()
    {
        return $this->getSetting("typeXpathInput","typeXpathInput");
    }

    public function getUrnXpath()
    {
        return $this->getSetting("urnXpath","urnXpath");
    }

    public function getDateXpath()
    {
        return $this->getSetting("dateXpath","dateXpath");
    }

    public function getNamespaces()
    {
        return $this->getSetting("namespaces","namespaces");
    }

    public function getTitleXpath()
    {
        return $this->getSetting("title_xpath","title_xpath");
    }

    public function getAuthorsXpath()
    {
        return $this->getSetting("authors_xpath","authors_xpath");
    }

    public function getProcessNumberXpath()
    {
        return $this->getSetting("process_number_xpath","process_number_xpath");
    }

    public function getSubmitterNameXpath()
    {
        return $this->getSetting("submitter_name_xpath","submitter_name_xpath");
    }

    public function getSubmitterEmailXpath()
    {
        return $this->getSetting("submitter_email_xpath","submitter_email_xpath");
    }

    public function getSubmitterNoticeXpath()
    {
        return $this->getSetting("submitter_notice_xpath","submitter_notice_xpath");
    }

}
