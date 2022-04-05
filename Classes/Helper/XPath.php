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

class XPath
{
    /**
     * clientConfigurationManager
     *
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $clientConfigurationManager;

    /**
     * Returns a new XPath object for the given DOMDocument,
     * all required namespaces are already registered.
     *
     * @param \DOMDocument $dom
     * @param string $namespaces
     * @return \DOMXPath
     */
    public static function create(\DOMDocument $dom, string $namespaces = '') : \DOMXPath
    {
        if (empty($namespaces)) {
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
            $clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);
            $namespaceConfiguration = explode(";", $clientConfigurationManager->getNamespaces());
        } else {
            $namespaceConfiguration = explode(";", $namespaces);
        }

        $xpath = new \DOMXPath($dom);

        foreach ($namespaceConfiguration as $key => $value) {
            $namespace = explode("=", $value);
            $xpath->registerNamespace($namespace[0], $namespace[1]);
        }

        return $xpath;
    }

}
