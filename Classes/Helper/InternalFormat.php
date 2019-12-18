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
use EWW\Dpf\Services\ParserGenerator;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class InternalFormat
{
    const rootNode = '//data/';

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
        return $domXPath = \EWW\Dpf\Helper\XPath::create($this->xml);
    }

    public function getDocumentType()
    {
        $xpath = $this->getXpath();

        $typeXpath = $this->clientConfigurationManager->getTypeXpath();
        $typeList = $xpath->query(self::rootNode . $typeXpath);

        return $typeList->item(0)->nodeValue;
    }

    public function setDocumentType($type)
    {
        $xpath = $this->getXpath();
        $typeXpath = $this->clientConfigurationManager->getTypeXpath();

        $dateNodes = $xpath->query(self::rootNode . $typeXpath);
        if ($dateNodes->length > 0) {
            $dateNodes->item(0)->nodeValue = $type;
        } else {
            $parserGenerator = new ParserGenerator();
            $parserGenerator->setXml($this->xml->saveXML());
            $parserGenerator->customXPath($typeXpath,true, $type);
            $this->xml = new \DOMDocument();
            $this->xml->loadXML($parserGenerator->getXMLData());
        }
    }

    public function getState()
    {
        $stateXpath = $this->clientConfigurationManager->getStateXpath();

        $xpath = $this->getXpath();

        $stateList = $xpath->query(self::rootNode . $stateXpath);
        return $stateList->item(0)->nodeValue;
    }

    public function setState($state)
    {
        $xpath = $this->getXpath();
        $stateXpath = $this->clientConfigurationManager->getStateXpath();

        $dateNodes = $xpath->query(self::rootNode . $stateXpath);
        if ($dateNodes->length > 0) {
            $dateNodes->item(0)->nodeValue = $state;
        } else {
            $parserGenerator = new ParserGenerator();
            $parserGenerator->setXml($this->xml->saveXML());
            $parserGenerator->customXPath($stateXpath,true, $state);
            $this->xml = new \DOMDocument();
            $this->xml->loadXML($parserGenerator->getXMLData());
        }
    }

    public function getProcessNumber()
    {
        $processNumberXpath = $this->clientConfigurationManager->getProcessNumberXpath();
        $xpath = $this->getXpath();

        if ($processNumberXpath) {
            $stateList = $xpath->query(self::rootNode . $processNumberXpath);
            return $stateList->item(0)->nodeValue;
        } else {
            return "";
        }
    }

    public function setProcessNumber($processNumber)
    {
        $xpath = $this->getXpath();
        $processNumberXpath = $this->clientConfigurationManager->getProcessNumberXpath();

        $dateNodes = $xpath->query(self::rootNode . $processNumberXpath);
        if ($dateNodes->length > 0) {
            $dateNodes->item(0)->nodeValue = $processNumber;
        } else {
            $parserGenerator = new ParserGenerator();
            $parserGenerator->setXml($this->xml->saveXML());
            $parserGenerator->customXPath($processNumberXpath,true, $processNumber);
            $this->xml = new \DOMDocument();
            $this->xml->loadXML($parserGenerator->getXMLData());
        }
    }

    public function getTitle()
    {
        $titleXpath = $this->clientConfigurationManager->getTitleXpath();
        $xpath = $this->getXpath();

        if (!$titleXpath) {
            $titleXpath = "titleInfo/title";
        }

        $stateList = $xpath->query(self::rootNode . $titleXpath);
        return $stateList->item(0)->nodeValue;
    }

    public function getAuthors()
    {
        $authorsXpath = $this->clientConfigurationManager->getAuthorsXpath();
        $xpath = $this->getXpath();

        if ($authorsXpath) {
            $stateList = $xpath->query(self::rootNode . $authorsXpath);
            $authorArray = [];
            foreach ($stateList as $key => $value) {
                $authorArray[] = $value->nodeValue;
            }
            return $authorArray;
        } else {
            return "";
        }


    }

    public function getFiles()
    {
        $xpath = $this->getXpath();

        $fileXpath = $this->clientConfigurationManager->getFileXpath();

        $fileNodes = $xpath->query(self::rootNode . $fileXpath);
        $files = [];

        foreach ($fileNodes as $file) {
            $fileAttrArray = [];
            foreach ($file->childNodes as $fileAttributes) {
                $fileAttrArray[$fileAttributes->tagName] = $fileAttributes->nodeValue;
            }
            $files[] = $fileAttrArray;
        }

        return $files;

    }

    public function setDateIssued($date) {
        $xpath = $this->getXpath();
        $dateXpath = $this->clientConfigurationManager->getDateXpath();

        $dateNodes = $xpath->query(self::rootNode . $dateXpath);
        if ($dateNodes->length > 0) {
            $dateNodes->item(0)->nodeValue = $date;
        } else {
            $parserGenerator = new ParserGenerator();
            $parserGenerator->setXml($this->xml->saveXML());
            $parserGenerator->customXPath($dateXpath,true, $date);
            $this->xml = new \DOMDocument($parserGenerator->getXMLData());
        }

    }

    public function getDateIssued() {
        $xpath = $this->getXpath();
        $dateXpath = $this->clientConfigurationManager->getDateXpath();

        $dateNodes = $xpath->query(self::rootNode . $dateXpath);

        return $dateNodes->item(0)->nodeValue;

    }

    public function removeDateIssued()
    {
        $xpath = $this->getXpath();
        $dateXpath = $this->clientConfigurationManager->getDateXpath();

        $dateNodes = $xpath->query(self::rootNode . $dateXpath);
        if ($dateNodes->length > 0) {
            $dateNodes->item(0)->parentNode->removeChild($dateNodes->item(0));
        }

    }

    public function hasQucosaUrn()
    {
        $xpath = $this->getXpath();
        $urnXpath = $this->clientConfigurationManager->getUrnXpath();

        $urnNodes = $xpath->query(self::rootNode . $urnXpath);
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

        $urnNodes = $xpath->query(self::rootNode . $urnXpath);
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

        if ($rootNode) {

            $urnNodes = $xpath->query(self::rootNode . $urnXpath);
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
                $rootNode->appendChild($newElement);
            }

        } else {
            throw new \Exception('Invalid xml data.');
        }


    }

    public function clearAllUrn()
    {
        $xpath = $this->getXpath();
        $urnXpath = $this->clientConfigurationManager->getUrnXpath();

        $urnNodes = $xpath->query(self::rootNode . $urnXpath);
        if ($urnNodes->length > 0) {
            $urnNodes->item(0)->parentNode->removeChild($urnNodes->item(0));
        }

    }

    public function getSubmitterEmail() {
        $xpath = $this->getXpath();
        $submitterXpath = $urnXpath = $this->clientConfigurationManager->getSubmitterEmailXpath();

        $dateNodes = $xpath->query(self::rootNode . $submitterXpath);
        if (!$dateNodes) {
            return '';
        } else {
            return $dateNodes->item(0)->nodeValue;
        }

    }

    public function getSubmitterName() {
        $xpath = $this->getXpath();
        $submitterXpath = $urnXpath = $this->clientConfigurationManager->getSubmitterNameXpath();

        $dateNodes = $xpath->query(self::rootNode . $submitterXpath);

        if (!$dateNodes) {
            return '';
        } else {
            return $dateNodes->item(0)->nodeValue;
        }
    }

    public function getSubmitterNotice() {
        $xpath = $this->getXpath();
        $submitterXpath = $urnXpath = $this->clientConfigurationManager->getSubmitterNoticeXpath();

        $dateNodes = $xpath->query(self::rootNode . $submitterXpath);

        if (!$dateNodes) {
            return '';
        } else {
            return $dateNodes->item(0)->nodeValue;
        }
    }

}
