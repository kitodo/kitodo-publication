<?php
namespace EWW\Dpf\ViewHelpers;

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

class FileUrlViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * Secret API key for delivering inactive documents.
     * @var string
     */
    private $secretKey;

    /**
     * Initialize secret key from plugin TYPOScript configuration.
     */
    public function initialize() {
        parent::initialize();

        $configurationManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $settings = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        if (isset($settings['plugin.']['tx_dpf.']['settings.']['deliverInactiveSecretKey'])) {
            $this->secretKey = $settings['plugin.']['tx_dpf.']['settings.']['deliverInactiveSecretKey'];
        }
    }

    /**
     *
     * @param string $uri
     *
     */
    public function render($uri)
    {
        $fileUri = $this->buildFileUri($uri);

        // pass configured API secret key parameter to enable dissemination for inactive documents
        if (isset($this->secretKey)) {
            $fileUri .= '?tx_dpf[deliverInactive]=' . $this->secretKey;
        }

        return $fileUri;
    }

    /**
     * Construct file URI
     */
    protected function buildFileUri($uri)
    {
        $uploadFileUrl = new \EWW\Dpf\Helper\UploadFileUrl;

        if (strpos(strtolower($uri), "datastreams")) {
            $regex = '/\/(\w*:\d*)\/datastreams\/(\w*-\d*)/';
            preg_match($regex, $uri, $treffer);

            if (!empty($treffer)) {
                $qid = $treffer[1];
                $fid = $treffer[2];
                $uri = $uploadFileUrl->getBaseUrl() . '/api/' . urlencode($qid) . '/attachment/' . $fid;
            }
        }

        $host = parse_url($uri, PHP_URL_HOST);

        // If in docker (development) mode: IP address needs to be replaced by "localhost", since docker container IP addresses
        // are not reachable from TYPO3 frontend.
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $settings = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS);
        if (
            $settings['development']['docker'] && (
                $host == '127.0.0.1' ||
                !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
                )
            )
        ) {
            $uri = str_replace($host, "localhost", $uri);
        }

        return $uri;
    }

}
