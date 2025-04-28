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
    protected $client = null;

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

        if (TYPO3_MODE === 'BE') {
            $selectedPageId = (int) \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id');
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
                \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS
            );
        }

        $this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dpf']);
    }

    /**
     * Get a setting value.
     *
     * Checks TypoScript settings first, then client object, and finally extension configuration.
     *
     * @param string $settingName The name of the setting to retrieve
     * @param string|null $extConfig Optional extension configuration key fallback
     * @return string|null The setting value or null if not found
     */
    public function getSetting($settingName, $extConfig = null)
    {
        $setting = null;

        // 1. Try to get from TypoScript settings if available
        if (is_array($this->settings) && isset($this->settings[$settingName])) {
            $setting = trim($this->settings[$settingName]);
        }

        // 2. If TypoScript setting is empty, try client object
        if (empty($setting) && $this->client) {
            $method = 'get' . ucfirst($settingName);
            if (method_exists($this->client, $method)) {
                $setting = trim($this->client->{$method}());
            }
        }

        // 3. If still empty, use extension configuration
        if (empty($setting) && $extConfig && isset($this->extensionConfiguration[$extConfig])) {
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
        return $this->getSetting("swordHost", "swordHost");
    }

    public function getSwordUser()
    {
        return $this->getSetting("swordUser", "swordUser");
    }

    public function getSwordPassword()
    {
        return $this->getSetting("swordPassword", "swordPassword");
    }

    public function getSwordCollectionNamespace()
    {
        return $this->getSetting("swordCollectionNamespace", "swordCollectionNamespace");
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

    public function getElasticSearchHost()
    {
        return $this->getSetting("elasticSearchHost", "elasticSearchHost");
    }

    public function getElasticSearchPort()
    {
        return $this->getSetting("elasticSearchPort", "elasticSearchPort");
    }

    public function getUploadDirectory()
    {
        return $this->getSetting("uploadDirectory", "uploadDirectory");
    }

    public function getUploadDomain()
    {
        return $this->getSetting("uploadDomain", "uploadDomain");
    }

    public function getEReaderUrl()
    {
        return $this->getSetting("ereaderUrl", "ereaderUrl");
    }
}
