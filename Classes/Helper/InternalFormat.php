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
use EWW\Dpf\Services\Storage\FileId;
use EWW\Dpf\Services\XPathXMLGenerator;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use EWW\Dpf\Domain\Model\File;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use DOMNode;
use Exception;

class InternalFormat
{
    const rootNode = '//data/';

    const VALUE_TRUE = 'true';
    const VALUE_FALSE = 'false';
    const VALUE_UNKNOWN = 'unknown';

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

    /**
     * @var int
     */
    protected $clientPid = 0;

    /**
     * InternalFormat constructor.
     * @param string $xml
     * @param int $clientPid
     */
    public function __construct(string $xml, $clientPid = 0)
    {
        $this->clientPid = $clientPid;

        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $this->clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);

        if ($clientPid) {
            $this->clientConfigurationManager->setConfigurationPid($clientPid);
        }

        $this->setXml($xml);
    }

    public function setXml($xml)
    {
        if (empty($xml)) {
            $xml = "<data></data>";
        }

        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $this->xml = $dom;
    }

    public function getXml()
    {
        return $this->xml->saveXML();
    }

    public function getDocument()
    {
        return $this->xml;
    }

    public function getXpath()
    {
        return $domXPath = \EWW\Dpf\Helper\XPath::create($this->xml);
    }

    public function getDocumentType()
    {
        $typeXpath = $this->clientConfigurationManager->getTypeXpath();
        return $this->getValue($typeXpath);
    }

    public function setDocumentType($type)
    {
        $typeXpath = $this->clientConfigurationManager->getTypeXpath();
        $this->setValue($typeXpath, $type);
    }

    // TODO: deprecated
    public function getRepositoryState()
    {
        $stateXpath = $this->clientConfigurationManager->getStateXpath();
        return $this->getValue($stateXpath);
    }

    // TODO: deprecated
    public function setRepositoryState($state)
    {
        $stateXpath = $this->clientConfigurationManager->getStateXpath();
        $this->setValue($stateXpath, $state);
    }

    public function getProcessNumber()
    {
        $processNumberXpath = $this->clientConfigurationManager->getProcessNumberXpath();
        if ($processNumberXpath) {
            return $this->getValue($processNumberXpath);
        } else {
            return "";
        }
    }

    public function setProcessNumber($processNumber)
    {
        $processNumberXpath = $this->clientConfigurationManager->getProcessNumberXpath();
        $this->setValue($processNumberXpath, $processNumber);
    }

    public function getTitle()
    {
        $titleXpath = $this->clientConfigurationManager->getTitleXpath();
        if (empty($titleXpath)) return;

        $xpath = $this->getXpath();
        $stateList = $xpath->query(self::rootNode . $titleXpath);
        if ($stateList) {
            return $stateList->item(0)->nodeValue;
        }
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $titleXpath = $this->clientConfigurationManager->getTitleXpath();
        $this->setValue($titleXpath, $title);
    }

    /**
     * Gets all file data found in the xml data file node. Usage attribute will be ignored.
     *
     * @return array
     */
    public function getFiles()
    {
        $files = [];

        $fileXpath = $this->clientConfigurationManager->getFileXpath();
        if (empty($fileXpath)) return [];

        $xpath = $this->getXpath();
        $fileNodes = $xpath->query(self::rootNode . $fileXpath);

        if ($fileNodes) foreach ($fileNodes as $file) {
            $fileAttrArray = [
                'id' => '',
                'mimetype' => '',
                'href' => '',
                'title' => '',
                'download' => false,
                'archive' => false,
                'deleted' => false
            ];

            $fileIdXpath = $this->clientConfigurationManager->getFileIdXpath();
            $fileMimetypeXpath = $this->clientConfigurationManager->getFileMimetypeXpath();
            $fileHrefXpath = $this->clientConfigurationManager->getFileHrefXpath();
            $fileDownloadXpath = $this->clientConfigurationManager->getFileDownloadXpath();
            $fileArchiveXpath = $this->clientConfigurationManager->getFileArchiveXpath();
            $fileDeletedXpath = $this->clientConfigurationManager->getFileDeletedXpath();
            $fileTitleXpath = $this->clientConfigurationManager->getFileTitleXpath();

            foreach ($file->childNodes as $fileAttributes) {
                switch ($fileAttributes->tagName) {
                    case $fileIdXpath:
                        $fileAttrArray['id'] = $fileAttributes->nodeValue;
                        break;

                    case $fileMimetypeXpath:
                        $fileAttrArray['mimetype'] = $fileAttributes->nodeValue;
                        break;

                    case $fileHrefXpath:
                        $fileAttrArray['href'] = $fileAttributes->nodeValue;
                        break;

                    case $fileTitleXpath:
                        $fileAttrArray['title'] = $fileAttributes->nodeValue;
                        break;

                    case $fileDownloadXpath:
                        $fileAttrArray['download'] = !empty($fileAttributes->nodeValue);
                        break;

                    case $fileArchiveXpath:
                        $fileAttrArray['archive'] = !empty($fileAttributes->nodeValue);
                        break;

                    case $fileDeletedXpath:
                        $fileAttrArray['deleted'] = !empty($fileAttributes->nodeValue);
                        break;
                }
            }
            $files[] = $fileAttrArray;
        }

        return $files;
    }

    public function setDateIssued($date)
    {
        $dateXpath = $this->clientConfigurationManager->getDateXpath();
        $this->setValue($dateXpath, $date);
    }

    public function getDateIssued()
    {
        $dateXpath = $this->clientConfigurationManager->getDateXpath();
        return $this->getValue($dateXpath);
    }

    public function removeDateIssued()
    {
        $xpath = $this->getXpath();
        $dateXpath = $this->clientConfigurationManager->getDateXpath();
        if (empty($dateXpath)) return;

        $dateNodes = $xpath->query(self::rootNode . $dateXpath);
        if ($dateNodes && $dateNodes->length > 0) {
            $dateNodes->item(0)->parentNode->removeChild($dateNodes->item(0));
        }
    }

    public function hasPrimaryUrn()
    {
        $xpath = $this->getXpath();
        $primaryUrnXpath = $this->clientConfigurationManager->getPrimaryUrnXpath();
        if (empty($primaryUrnXpath)) return false;

        $urnNodes = $xpath->query(self::rootNode . $primaryUrnXpath);
        if ($urnNodes->length > 0) {
            return true;
        } else {
            return false;
        }
    }

    public function getPrimaryUrn()
    {
        $xpath = $this->getXpath();
        $primaryUrnXpath = $this->clientConfigurationManager->getPrimaryUrnXpath();
        if (empty($primaryUrnXpath)) return false;

        $urnNodes = $xpath->query(self::rootNode . $primaryUrnXpath);
        if ($urnNodes && $urnNodes->length > 0) {
            return $urnNodes->item(0)->nodeValue;
        } else {
            return false;
        }
    }

    public function setPrimaryUrn($urn)
    {
        $primaryUrnXpath = $this->clientConfigurationManager->getPrimaryUrnXpath();
        $this->setValue($primaryUrnXpath, $urn);
    }

    public function clearAllUrn()
    {
        $xpath = $this->getXpath();
        $urnXpath = $this->clientConfigurationManager->getUrnXpath();
        if (empty($urnXpath)) return;

        $urnNodes = $xpath->query(self::rootNode . $urnXpath);
        if ($urnNodes) foreach ($urnNodes as $urnNode) {
            $urnNode->parentNode->removeChild($urnNode);
        }

        $primaryUrnXpath = $this->clientConfigurationManager->getPrimaryUrnXpath();
        if (empty($primaryUrnXpath)) return;
        $primaryUrnNodes = $xpath->query(self::rootNode . $primaryUrnXpath);
        if ($primaryUrnNodes) foreach ($primaryUrnNodes as $primaryUrnNode) {
            $primaryUrnNode->parentNode->removeChild($primaryUrnNode);
        }
    }

    public function getSubmitterEmail()
    {
        $xpath = $this->getXpath();
        $submitterXpath = $this->clientConfigurationManager->getSubmitterEmailXpath();
        if ($submitterXpath) {
            $nodes = $xpath->query(self::rootNode . $submitterXpath);
            return ($nodes) ? $nodes->item(0)->nodeValue : '';
        }
        return '';
    }

    public function getSubmitterName()
    {
        $xpath = $this->getXpath();
        $submitterXpath = $this->clientConfigurationManager->getSubmitterNameXpath();
        if ($submitterXpath) {
            $nodes = $xpath->query(self::rootNode . $submitterXpath);
            return ($nodes) ? $nodes->item(0)->nodeValue : '';
        }
        return '';
    }

    public function getSubmitterNotice()
    {
        $xpath = $this->getXpath();
        $submitterXpath  = $this->clientConfigurationManager->getSubmitterNoticeXpath();
        if ($submitterXpath) {
            $nodes = $xpath->query(self::rootNode . $submitterXpath);
            return ($nodes) ? $nodes->item(0)->nodeValue : '';
        }
        return '';
    }

    /**
     * @return string
     */
    public function getCreator()
    {
        $creatorXpath = $this->clientConfigurationManager->getCreatorXpath();
        $creator = $this->getValue($creatorXpath);

        if (isset($creator) === true && $creator !== '') {
            return $creator;
        }

        return '0';
    }

    /**
     * @param string $creator
     */
    public function setCreator(string $creator)
    {
        $creatorXpath = $this->clientConfigurationManager->getCreatorXpath();
        $this->setValue($creatorXpath, $creator);
    }

    public function getCreationDate()
    {
        $xpath = $this->clientConfigurationManager->getCreationDateXpath();
        return $this->getValue($xpath);
    }

    public function setCreationDate($creationDate)
    {
        $xpath = $this->clientConfigurationManager->getCreationDateXpath();
        $this->setValue($xpath, $creationDate);
    }

    // TODO: deprecated
    public function getRepositoryCreationDate()
    {
        $xpath = $this->clientConfigurationManager->getRepositoryCreationDateXpath();
        return $this->getValue($xpath);
    }

    // TODO: deprecated
    public function getRepositoryLastModDate()
    {
        $xpath = $this->clientConfigurationManager->getRepositoryLastModDateXpath();
        return $this->getValue($xpath);
    }

    public function getPublishingYear()
    {
        $publishingYearXpath = $this->clientConfigurationManager->getPublishingYearXpath();
        return $this->getValue($publishingYearXpath);
    }

    /**
     * @return array
     */
    public function getSearchYear()
    {
        $yearXpath     = $this->clientConfigurationManager->getSearchYearXpaths();
        $yearXpathList = explode(";", trim($yearXpath, " ;"));
        $xpath         = $this->getXpath();
        $values        = [];

        foreach ($yearXpathList as $yearXpathItem) {
            if (empty($yearXpathItem)) continue;

            $elements = $xpath->query(self::rootNode . trim($yearXpathItem));

            if ($elements) foreach ($elements as $element) {
                $year = trim($element->nodeValue);
                if (strlen($year) <= 4) {
                    $values[] = $year;
                } else {
                    if ($timeStamp = strtotime($year)) {
                        $values[] = date("Y", $timeStamp);
                    }
                }
            }
        }

        return $values;
    }

    /**
     * @return array
     */
    public function getPublishers()
    {
        $publisherXpath     = $this->clientConfigurationManager->getPublisherXpaths();
        $publisherXpathList = explode(";", trim($publisherXpath, " ;"));
        $xpath              = $this->getXpath();
        $values             = [];

        foreach ($publisherXpathList as $publisherXpathItem) {
            if (empty($publisherXpathItem)) continue;

            $elements = $xpath->query(self::rootNode . trim($publisherXpathItem));
            if ($elements) foreach ($elements as $element) {
                $values[] = trim($element->nodeValue);
            }
        }

        return $values;
    }

    public function getOriginalSourceTitle()
    {
        $originalSourceTitleXpath = $this->clientConfigurationManager->getOriginalSourceTitleXpath();
        return $this->getValue($originalSourceTitleXpath);
    }

    /**
     * @return string
     */
    public function getSourceDetails()
    {
        if (empty($sourceDetailsXpaths)) {
            return '';
        }

        $xpath = $this->getXpath();
        $data = [];
        $sourceDetailsXpaths = $this->clientConfigurationManager->getSourceDetailsXpaths();
        $sourceDetailsXpathList = explode(";", trim($sourceDetailsXpaths, " ;"));
        $dataNodes = [];

        foreach ($sourceDetailsXpathList as $sourceDetailsXpathItem) {
            if (empty($sourceDetailsXpathItem)) continue;
            $dataNodes[] = $xpath->query(self::rootNode . trim($sourceDetailsXpathItem));
        }

        foreach ($dataNodes as $dataNode) {
            if (is_iterable($dataNode)) foreach ($dataNode as $node) {
                if ($node->hasChildNodes()) {
                    foreach ($node->childNodes as $n) {
                        $data[] = preg_replace('/\s+/', ' ', $n->textContent);
                    }
                } else {
                    $data[] = preg_replace('/\s+/', ' ', $node->textContent);
                }
            }
        }

        $output = trim(implode(' ', $data));
        $output = preg_replace('/\s+/ ', ' ', $output);
        return $output;
    }

    /**
     * Get all related FOB-IDs
     *
     * @return array
     */
    public function getPersonFisIdentifiers(): array
    {
        $identifiers = [];
        $personXpath = $this->clientConfigurationManager->getPersonXpath();
        if (!empty($personXpath)) {
            $xpath = $this->getXpath();
            $personNodes = $xpath->query(self::rootNode . $personXpath);

            $fisIdentifierXpath =  $this->clientConfigurationManager->getPersonFisIdentifierXpath();
            if ($personNodes && !empty($fisIdentifierXpath)) foreach ($personNodes as $node) {
                $identifierNodes = $xpath->query($fisIdentifierXpath, $node);
                if ($identifierNodes->length > 0) {
                    $identifiers[] = $identifierNodes->item(0)->nodeValue;
                }
            }
        }
        return $identifiers;
    }

    /**
     * @return string
     */
    public function getDepositLicense()
    {
        $depositLicenseXpath = $this->clientConfigurationManager->getDepositLicenseXpath();
        return $this->getValue($depositLicenseXpath);
    }

    /**
     * @return array
     */
    public function getNotes()
    {
        $notes = array();
        $notesXpath = $this->clientConfigurationManager->getAllNotesXpath();
        if (!empty($notesXpath)) {
            $xpath = $this->getXpath();
            $notesNodes = $xpath->query(self::rootNode . $notesXpath);
            if ($notesNodes) for ($i = 0; $i < $notesNodes->length; $i++) {
                $notes[] = $notesNodes->item($i)->nodeValue;
            }
        }
        return $notes;
    }

    public function addNote($noteContent)
    {
        $notesXpath = $this->clientConfigurationManager->getPrivateNotesXpath();

        $parserGenerator = new ParserGenerator($this->clientPid);
        $parserGenerator->setXml($this->xml->saveXML());
        $parserGenerator->customXPath($notesXpath, true, $noteContent);
        $this->xml = new \DOMDocument();
        $this->xml->loadXML($parserGenerator->getXMLData());
    }

    public function getAuthors()
    {
        return $this->getPersons($this->clientConfigurationManager->getPersonAuthorRole());
    }

    public function getPersonPublishers()
    {
        return $this->getPersons($this->clientConfigurationManager->getPersonPublisherRole());
    }

    /**
     * Get persons of the given role
     *
     * @param string $role
     * @return array
     */
    public function getPersons($role = '')
    {
        $persons = [];

        $personXpath = $this->clientConfigurationManager->getPersonXpath();

        if (empty($personXpath)) return [];

        $xpath = $this->getXpath();
        $personNodes = $xpath->query(self::rootNode . $personXpath);

        $familyXpath = $this->clientConfigurationManager->getPersonFamilyXpath();
        $givenXpath = $this->clientConfigurationManager->getPersonGivenXpath();
        $roleXpath = $this->clientConfigurationManager->getPersonRoleXpath();
        $fisIdentifierXpath =  $this->clientConfigurationManager->getPersonFisIdentifierXpath();
        $affiliationXpath =  $this->clientConfigurationManager->getPersonAffiliationXpath();
        $affiliationIdentifierXpath =  $this->clientConfigurationManager->getPersonAffiliationIdentifierXpath();

        if ($personNodes) foreach ($personNodes as $key => $personNode) {
            $person['affiliations'] = [];
            $affiliationNodes = empty($affiliationXpath) ? [] : $xpath->query($affiliationXpath, $personNode);
            foreach ($affiliationNodes as $key => $affiliationNode) {
                $person['affiliations'][] = $affiliationNode->nodeValue;
            }

            $person['affiliationIdentifiers'] = [];
            $affiliationIdentifierNodes = empty($affiliationIdentifierXpath) ? [] : $xpath->query($affiliationIdentifierXpath, $personNode);
            if ($affiliationIdentifierNodes) foreach ($affiliationIdentifierNodes as $key => $affiliationIdentifierNode) {
                $person['affiliationIdentifiers'][] = $affiliationIdentifierNode->nodeValue;
            }

            $given = '';
            $family = '';
            $givenNodes =  empty($givenXpath) ? [] : $xpath->query($givenXpath, $personNode);
            if ($givenNodes->length > 0) {
                $given = $givenNodes->item(0)->nodeValue;
            }
            $familyNodes = empty($familyXpath) ? [] : $xpath->query($familyXpath, $personNode);
            if ($familyNodes->length > 0) {
                $family = $familyNodes->item(0)->nodeValue;
            }
            $person['given'] = trim($given);
            $person['family'] = trim($family);

            $name = [];
            if ($person['given']) {
                $name[] = $person['given'];
            }
            if ($person['family']) {
                $name[] = $person['family'];
            }

            $person['name'] = implode(' ', $name);

            $person['role'] = '';
            $roleNodes = empty($roleXpath) ? [] : $xpath->query($roleXpath, $personNode);
            if ($roleNodes->length > 0) {
                $person['role'] = $roleNodes->item(0)->nodeValue;
            }

            $person['fobId'] = '';
            $identifierNodes = empty($fisIdentifierXpath) ? [] : $xpath->query($fisIdentifierXpath, $personNode);
            if ($identifierNodes->length > 0) {
                $person['fobId'] = $identifierNodes->item(0)->nodeValue;
            }

            $person['index'] = $key;
            $persons[] = $person;
        }

        if ($role) {
            $result = [];
            foreach ($persons as $person) {
                if ($person['role'] == $role)
                    $result[] = $person;
            }
            return $result;
        } else {
            return $persons;
        }
    }

    /**
     * @param string $fisId
     */
    public function setFisId($fisId)
    {
        $fisIdXpath =  $this->clientConfigurationManager->getFisIdXpath();
        $this->setValue($fisIdXpath, $fisId);
    }

    /**
     * @return string
     */
    public function getFisId()
    {
        $fisIdXpath =  $this->clientConfigurationManager->getFisIdXpath();
        return $this->getValue($fisIdXpath);
    }

    /**
     * @return array
     */
    public function getCollections()
    {
        $collections = array();

        $collectionXpath = $this->clientConfigurationManager->getCollectionXpath();
        if (empty($collectionXpath)) return [];

        $xpath = $this->getXpath();
        $collectionNodes = $xpath->query(self::rootNode . $collectionXpath);

        if ($collectionNodes) for ($i = 0; $i < $collectionNodes->length; $i++) {
            $collections[] = $collectionNodes->item($i)->nodeValue;
        }

        return $collections;
    }


    /**
     * @return string
     */
    public function getTextType()
    {
        $textTypeXpath =  $this->clientConfigurationManager->getTextTypeXpath();
        return $this->getValue($textTypeXpath);
    }

    /**
     * @return string
     */
    public function getOpenAccessForSearch()
    {
        $openAccessOtherVersionXpath = $this->clientConfigurationManager->getOpenAccessOtherVersionXpath();
        if ($openAccessOtherVersionXpath !== '') {
            $xpath = $this->getXpath();
            $openAccessOtherVersionElements = $xpath->query(self::rootNode . trim($openAccessOtherVersionXpath));
            if ($openAccessOtherVersionElements) {
                $openAccessValues = $this->clientConfigurationManager->getOpenAccessValues();
                foreach ($openAccessOtherVersionElements as $element) {
                    if (
                        strtolower(trim($element->nodeValue)) === strtolower($openAccessValues['true']) ||
                        strtolower(trim($element->nodeValue)) === strtolower($openAccessValues['trueUri'])
                    ) {
                        return self::VALUE_TRUE;
                    }
                }
            }

            $openAccessXpath = $this->clientConfigurationManager->getOpenAccessXpath();
            $openAccess = $this->getValue($openAccessXpath);
            if (
                strtolower($openAccess) === strtolower($openAccessValues['true']) ||
                strtolower($openAccess) === strtolower($openAccessValues['trueUri'])
            ) {
                return self::VALUE_TRUE;
            }
        }
        return self::VALUE_FALSE;
    }

    /**
     * @return string
     */
    public function getPeerReviewForSearch()
    {
        $xpath = $this->getXpath();

        $peerReviewOtherVersionXpath    = $this->clientConfigurationManager->getPeerReviewOtherVersionXpath();

        if (!$peerReviewOtherVersionXpath) {
            return self::VALUE_UNKNOWN;
        }

        $peerReviewOtherVersionElements = $xpath->query(self::rootNode . trim($peerReviewOtherVersionXpath));

        $peerReviewValues = $this->clientConfigurationManager->getPeerReviewValues();

        if ($peerReviewOtherVersionElements) {
            foreach ($peerReviewOtherVersionElements as $element) {
                if (strtolower(trim($element->nodeValue) === strtolower($peerReviewValues['true']))) {
                    return self::VALUE_TRUE;
                }
            }
        }

        $peerReviewXpath = $this->clientConfigurationManager->getPeerReviewXpath();
        $peerReview = $this->getValue($peerReviewXpath);
        if (strtolower($peerReview) === strtolower($peerReviewValues['true'])) {
            return self::VALUE_TRUE;
        }

        if ($peerReviewOtherVersionElements) {
            foreach ($peerReviewOtherVersionElements as $element) {
                if (strtolower(trim($element->nodeValue) === strtolower($peerReviewValues['false']))) {
                    return self::VALUE_FALSE;
                }
            }
        }

        if (strtolower($peerReview) === strtolower($peerReviewValues['false'])) {
            return self::VALUE_FALSE;
        }

        return self::VALUE_UNKNOWN;
    }

    /**
     * @return string
     */
    public function getLicense()
    {
        $licenseXpath = $this->clientConfigurationManager->getLicenseXpath();
        return $this->getValue($licenseXpath);
    }

    /**
     * @return string
     */
    public function getFrameworkAgreementId()
    {
        $xpath = $this->clientConfigurationManager->getFrameworkAgreementIdXpath();
        return $this->getValue($xpath);
    }

    /**
     * @param string $xpathString
     * @return string
     */
    protected function getValue($xpathString)
    {
        $xpath = $this->getXpath();
        if (empty($xpathString)) {
            return '';
        }
        $nodeList = $xpath->query(self::rootNode . $xpathString);
        return ($nodeList && $nodeList->length > 0) ? $nodeList->item(0)->nodeValue : '';
    }

    /**
     * @param string $xpathString
     * @param string $value
     */
    protected function setValue(string $xpathString, string $value)
    {
        $xpath = $this->getXpath();
        if ($xpathString === '') {
            return;
        }
        $nodes = $xpath->query(self::rootNode . $xpathString);
        if ($nodes->length > 0) {
            $nodes->item(0)->nodeValue = $value;
        } elseif (isset($value) === true && $value !== '') {
            $parserGenerator = new ParserGenerator($this->clientPid);
            $parserGenerator->setXml($this->xml->saveXML());
            $parserGenerator->customXPath($xpathString, true, $value);
            $this->xml = new \DOMDocument();
            $this->xml->loadXML($parserGenerator->getXMLData());
        }
    }

    /**
     * Removes all file nodes from the internal xml
     */
    public function removeAllFiles()
    {
        $xpath = $this->getXpath();
        $fileXpath = $this->clientConfigurationManager->getFileXpath();
        if (empty($fileXpath)) return;
        $fileNodes = $xpath->query(self::rootNode . $fileXpath);
        if ($fileNodes) foreach ($fileNodes as $fileNode) {
            $fileNode->parentNode->removeChild($fileNode);
        }
    }

    /**
     * @param DOMNode $fileNode
     * @param string $nodeXpath
     * @param string $value
     */
    public function setFileData(DOMNode $fileNode, string $nodeXpath, string $value)
    {
        if ($fileNode && !empty($nodeXpath)) {
            $xpath = $this->getXpath();
            $nodes = $xpath->query($nodeXpath, $fileNode);

            if ($nodes && $nodes->length > 0) {
                $nodes->item(0)->nodeValue = $value;
            } else {
                /** @var XPathXMLGenerator $xPathXMLGenerator */
                $xPathXMLGenerator = new XPathXMLGenerator();
                $xPathXMLGenerator->generateXmlFromXPath($nodeXpath . "='" . $value . "'");

                // FIXME: XPATHXmlGenerator XPATH does not generate any namespaces,
                // which DOMDocument cannot cope with. Actually, namespaces should not be necessary here,
                // since it is about child elements that are then added to the overall XML.
                libxml_use_internal_errors(true);
                $dom = new \DOMDocument();
                $domLoaded = $dom->loadXML($xPathXMLGenerator->getXML());
                libxml_use_internal_errors(false);

                if ($domLoaded) {
                    $newField = $this->xml->importNode($dom->firstChild, true);
                    $fileNode->appendChild($newField);
                }
            }
        }
    }

    /**
     * @param ObjectStorage<File> $files
     * @throws \Exception
     */
    public function completeFileData(ObjectStorage $files)
    {
        $fileXpath = $this->clientConfigurationManager->getFileXpath();
        $hrefXpath = $this->clientConfigurationManager->getFileHrefXpath();
        $titleXpath = $this->clientConfigurationManager->getFileTitleXpath();
        $mimeTypeXpath = $this->clientConfigurationManager->getFileMimetypeXpath();
        $idXpath = $this->clientConfigurationManager->getFileIdXpath();
        $deletedXpath = $this->clientConfigurationManager->getFileDeletedXpath();
        $archiveXpath = $this->clientConfigurationManager->getFileArchiveXpath();

        /** @var File $file */
        foreach ($files as $file) {

            $dataStreamIdentifier = $file->getDatastreamIdentifier();

            if (!$file->isFileGroupDeleted()) {

                if ($file->isDeleted()) {

                    if (!empty($dataStreamIdentifier)) {
                        /** @var XPathXMLGenerator $xPathXMLGenerator */
                        $xPathXMLGenerator = new XPathXMLGenerator();
                        $xPathXMLGenerator->generateXmlFromXPath($fileXpath);

                        // FIXME: XPATHXmlGenerator XPATH does not generate any namespaces,
                        // which DOMDocument cannot cope with. Actually, namespaces should not be necessary here,
                        // since it is about child elements that are then added to the overall XML.
                        libxml_use_internal_errors(true);
                        $dom = new \DOMDocument();
                        $domLoaded = $dom->loadXML($xPathXMLGenerator->getXML());
                        libxml_use_internal_errors(false);

                        if ($domLoaded) {
                            $newFile = $this->xml->importNode($dom->firstChild, true);
                            // FIXME DOMNode has no such function. Is this even working?
                            $newFile->setAttribute('usage', 'delete');
                            $this->setFileData($newFile, $idXpath, $file->getDatastreamIdentifier());
                            $this->setFileData($newFile, $hrefXpath, $file->getLink());
                            $this->setFileData($newFile, $titleXpath, $file->getLabel());
                            $this->setFileData($newFile, $deletedXpath, 'yes');
                            $this->setFileData($newFile, $archiveXpath, $file->getArchive());
                            $this->setFileData($newFile, $mimeTypeXpath, $file->getContentType());
                            $this->xml->firstChild->appendChild($newFile);
                        }
                    }
                } else {
                    $xpath = $this->getXpath();
                    $fileNodes = $xpath->query(
                        self::rootNode . $fileXpath . '[./' . trim($idXpath, '@/ ') . '="' . $file->getFileIdentifier() . '"]'
                    );

                    if ($fileNodes && $fileNodes->length > 0) {
                        $fileId = new FileId($files);
                        $this->setFileData($fileNodes->item(0), $idXpath, $fileId->getId($file));
                        $this->setFileData($fileNodes->item(0), $mimeTypeXpath, $file->getContentType());
                    }
                }
            }
        }
    }

    /**
     * @param ObjectStorage<File> $files
     * @throws \Exception
     */
    public function updateFileHrefs(ObjectStorage $files)
    {
        $xpath = $this->getXpath();
        $fileXpath = $this->clientConfigurationManager->getFileXpath();
        $idXpath = $this->clientConfigurationManager->getFileIdXpath();
        $fileHrefXpath = $this->clientConfigurationManager->getFileHrefXpath();

        /** @var File $file */
        foreach ($files as $file) {

            $fileNodes = $xpath->query(
                self::rootNode . $fileXpath . '[./' . trim($idXpath, '@/ ') . '="' . $file->getFileIdentifier() . '"]'
            );

            if ($fileNodes && $fileNodes->length > 0) {
                $this->setFileData($fileNodes->item(0), $fileHrefXpath, $file->getLink());
            }
        }
    }
}
