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



class UploadFileUrl
{

    /**
    * clientConfigurationManager
    * 
    * @var \EWW\Dpf\Configuration\ClientConfigurationManager 
    * @inject
    */
    protected $clientConfigurationManager;


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
