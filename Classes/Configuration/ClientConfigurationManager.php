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
    	$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\Object\\ObjectManager');
        $clientRepository = $objectManager->get("EWW\\Dpf\\Domain\\Repository\\ClientRepository");

		if (TYPO3_MODE === 'BE')
		{
            $selectedPageId = (int) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');
            if ($selectedPageId)
            {
                $this->client = $clientRepository->findAll()->current();

                $configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\BackendConfigurationManager');
                $settings = $configurationManager->getConfiguration(NULL,NULL);
                $this->settings = $settings; //['settings'];
            }

		}
		else
		{
            $this->client = $clientRepository->findAll()->current();

    		$configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
    		$this->settings = $configurationManager->getConfiguration(
            	\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS
        	);

        }

        $this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dpf']);
    }	
   

    public function getSetting($settingName, $extConfig = NULL)
    {
        $setting = NULL;
        if ($this->client)
        {
            $setting = trim($this->client->{"get".ucfirst($settingName)}());
            if (!(is_string($setting) && $setting != "") && $extConfig)
            {
                $setting = trim($this->extensionConfiguration[$extConfig]);
            }
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

}
