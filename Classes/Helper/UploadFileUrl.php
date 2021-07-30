<?php
namespace EWW\Dpf\Helper;

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

use EWW\Dpf\Configuration\ClientConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class UploadFileUrl
{

    /**
     * clientConfigurationManager
     *
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     */
    protected $clientConfigurationManager;

    /**
     * UploadFileUrl constructor.
     * @param int $clientPid
     */
    public function __construct($clientPid = 0) {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $this->clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);
        if ($clientPid) {
            $this->clientConfigurationManager->setConfigurationPid($clientPid);
        }
    }


    public function getBaseUrl()
    {
        $uploadDomain = $this->clientConfigurationManager->getUploadDomain();

        $baseUrl = trim($uploadDomain, "/ ");

        if (empty($baseUrl)) {
            $protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === false ? 'http://' : 'https://';
            $baseUrl  = $protocol . $_SERVER['HTTP_HOST'];
        }

        return $baseUrl;
    }

    public function getDirectory()
    {
        $uploadDirectory = $this->clientConfigurationManager->getUploadDirectory();

        $uploadDirectory = trim($uploadDirectory, "/ ");

        $uploadDir = empty($uploadDirectory) ? "uploads/tx_dpf" : $uploadDirectory;

        return $uploadDir;
    }

    public function getUploadUrl()
    {
        return $this->getBaseUrl() . "/" . $this->getDirectory();
    }

}
