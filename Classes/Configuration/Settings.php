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
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

class Settings
{
    /**
     * @var array
     */
    protected $settings;

    function __construct()
    {
        $configurationManager = GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager'
        );

        $this->settings = $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
    }

    /**
     * @param $settingName
     * @return mixed|null
     */
    function getSettingByName($settingName) {
        if (isset($this->settings['plugin.']['tx_dpf.']['settings.'][$settingName])) {
            return $this->settings['plugin.']['tx_dpf.']['settings.'][$settingName];
        }

        return null;
    }
}
