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

    public function getBaseUrl()
    {

        $confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dpf']);
        $baseUrl = trim($confArr['uploadDomain'], "/ ");

        if (empty($baseUrl)) {
            $protocol = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === false ? 'http://' : 'https://';
            $baseUrl  = $protocol . $_SERVER['HTTP_HOST'];
        }

        return $baseUrl;
    }

    public function getDirectory()
    {

        $confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dpf']);

        $uploadDirectory = trim($confArr['uploadDirectory'], "/ ");

        $uploadDir = empty($uploadDirectory) ? "uploads/tx_dpf" : $uploadDirectory;

        return $uploadDir;
    }

    public function getUploadUrl()
    {
        return $this->getBaseUrl() . "/" . $this->getDirectory();
    }

}
