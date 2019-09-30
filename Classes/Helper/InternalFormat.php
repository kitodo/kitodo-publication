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

class InternalFormat
{

    /**
     * clientConfigurationManager
     *
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     */
    protected $clientConfigurationManager;

    /**
     * xml
     *
     * @var \DOMDocument
     */
    protected $xml;

    public function __construct($xml)
    {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $this->clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);

        $this->setXml($xml);
    }

    public function setXml($xml)
    {
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $this->xml = $dom;
    }

    public function getXml()
    {
        return $this->xml->saveXML();
    }

    public function getDocument() {
        return $this->xml;
    }

    public function getXpath()
    {
        return new \DOMXPath($this->xml);
    }

    public function getDocumentType()
    {
        $xpath = $this->getXpath();

        $typeXpath = $this->clientConfigurationManager->getTypeXpath();

        $typeList = $xpath->query($typeXpath);
        return $typeList->item(0)->nodeValue;
    }

    public function getState()
    {
        $stateXpath = $this->clientConfigurationManager->getStateXpath();

        $xpath = $this->getXpath();

        $stateList = $xpath->query($stateXpath);
        return $stateList->item(0)->nodeValue;
    }

    public function getTitle()
    {
        $xpath = $this->getXpath();

        $stateList = $xpath->query("titleInfo/title");
        return $stateList->item(0)->nodeValue;
    }

    public function getFiles()
    {
        $xpath = $this->getXpath();

        $fileXpath = $this->clientConfigurationManager->getFileXpath();

        $fileNodes = $xpath->query($fileXpath);

        $files = [];

        foreach ($fileNodes as $file) {
            $fileAttrArray = [];
            foreach ($file->childNodes as $fileAttributes) {
                $fileAttrArray[$fileAttributes->tagName] = $fileAttributes->nodeValue;
            }
            $files[] = $fileAttrArray;
        }

    }

    public function setDateIssued($date) {
        $xpath = $this->getXpath();
        $dateXpath = $this->clientConfigurationManager->getDateXpath();

        $dateNodes = $xpath->query($dateXpath);
        $dateNodes->item(0)->nodeValue = $date;

    }

    public function getDateIssued() {
        $xpath = $this->getXpath();
        $dateXpath = $this->clientConfigurationManager->getDateXpath();

        $dateNodes = $xpath->query($dateXpath);

        return $dateNodes->item(0)->nodeValue;

    }

    public function removeDateIssued()
    {
        $xpath = $this->getXpath();
        $dateXpath = $this->clientConfigurationManager->getDateXpath();

        $dateNodes = $xpath->query($dateXpath);
        if ($dateNodes->length > 0) {
            $dateNodes->item(0)->parentNode->removeChild($dateNodes->item(0));
        }

    }

    public function hasQucosaUrn()
    {
        $xpath = $this->getXpath();
        $urnXpath = $this->clientConfigurationManager->getUrnXpath();

        $urnNodes = $xpath->query($urnXpath);
        if ($urnNodes->length > 0) {
            return true;
        } else {
            return false;
        }

    }

    public function getQucosaUrn()
    {
        $xpath = $this->getXpath();
        $urnXpath = $this->clientConfigurationManager->getUrnXpath();

        $urnNodes = $xpath->query($urnXpath);
        if ($urnNodes->length > 0) {
            return $urnNodes->item(0)->nodeValue;
        } else {
            return false;
        }
    }

    public function addQucosaUrn($urn)
    {
        $xpath = $this->getXpath();
        $urnXpath = $this->clientConfigurationManager->getUrnXpath();

        $rootNode = $this->getDocument()->documentElement;

        if ($rootNode->length == 1) {

            $urnNodes = $xpath->query($urnXpath);
            if ($urnNodes->length > 0) {
                $urnNodes->item(0)->nodeValue = $urn;
            } else {
                $document = $this->getDocument();
                $xpathExplode = array_reverse(explode("/", $urnXpath));
                $i = 1;
                $newElement = null;
                foreach ($xpathExplode as $element) {
                    if ($i == 1) {
                        $newElement = $document->createElement($element);
                        $newElement->nodeValue = $urn;
                    } else {
                        $parentElement = $document->createElement($element);
                        $parentElement->appendChild($newElement);
                        $newElement = $parentElement;
                    }
                    $i++;
                }
                $document->appendChild($newElement);
            }

        } else {
            throw new \Exception('Invalid xml data.');
        }


    }

    public function clearAllUrn()
    {
        $xpath = $this->getXpath();
        $urnXpath = $this->clientConfigurationManager->getUrnXpath();

        $urnNodes = $xpath->query($urnXpath);
        if ($urnNodes->length > 0) {
            $urnNodes->item(0)->parentNode->removeChild($urnNodes->item(0));
        }

    }

    public function getSubmitterEmail() {
        $xpath = $this->getXpath();
        $submitterXpath = 'submitter/mail';

        $dateNodes = $xpath->query($submitterXpath);

        return $dateNodes->item(0)->nodeValue;
    }

    public function getSubmitterName() {
        $xpath = $this->getXpath();
        $submitterXpath = 'submitter/name';

        $dateNodes = $xpath->query($submitterXpath);

        return $dateNodes->item(0)->nodeValue;
    }

    public function getSubmitterNotice() {
        $xpath = $this->getXpath();
        $submitterXpath = 'submitter/notice';

        $dateNodes = $xpath->query($submitterXpath);

        return $dateNodes->item(0)->nodeValue;
    }

}
