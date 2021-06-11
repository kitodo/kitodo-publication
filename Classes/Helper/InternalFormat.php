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

    public function getDocument() {
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

    public function getRepositoryState()
    {
        $stateXpath = $this->clientConfigurationManager->getStateXpath();
        return $this->getValue($stateXpath);
    }

    public function setRepositoryState($state)
    {
        $stateXpath = $this->clientConfigurationManager->getStateXpath();
        $this->setValue($stateXpath,$state);
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
        $xpath = $this->getXpath();

        if (!$titleXpath) {
            $titleXpath = "titleInfo/title";
        }

        $stateList = $xpath->query(self::rootNode . $titleXpath);
        return $stateList->item(0)->nodeValue;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $titleXpath = $this->clientConfigurationManager->getTitleXpath();
        $this->setValue($titleXpath, $title);
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
        $dateXpath = $this->clientConfigurationManager->getDateXpath();
        $this->setValue($dateXpath, $date);
    }

    public function getDateIssued() {
        $dateXpath = $this->clientConfigurationManager->getDateXpath();
        return $this->getValue($dateXpath);
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

    public function hasPrimaryUrn()
    {
        $xpath = $this->getXpath();
        $primaryUrnXpath = $this->clientConfigurationManager->getPrimaryUrnXpath();

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

        $urnNodes = $xpath->query(self::rootNode . $primaryUrnXpath);
        if ($urnNodes->length > 0) {
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
        $primaryUrnXpath = $this->clientConfigurationManager->getPrimaryUrnXpath();

        $urnNodes = $xpath->query(self::rootNode . $urnXpath);
        foreach ($urnNodes as $urnNode) {
            $urnNode->parentNode->removeChild($urnNode);
        }

        $primaryUrnNodes = $xpath->query(self::rootNode . $primaryUrnXpath);
        foreach ($primaryUrnNodes as $primaryUrnNode) {
            $primaryUrnNode->parentNode->removeChild($primaryUrnNode);
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

    public function getCreator()
    {
        $creatorXpath = $this->clientConfigurationManager->getCreatorXpath();
        return $this->getValue($creatorXpath);
    }

    public function setCreator($creator)
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

    public function getRepositoryCreationDate()
    {
        $xpath = $this->clientConfigurationManager->getRepositoryCreationDateXpath();
        return $this->getValue($xpath);
    }

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
        $sourceDetailsXpathList = explode(";", trim($sourceDetailsXpaths," ;"));
        $dataNodes = [];

        foreach ($sourceDetailsXpathList as $sourceDetailsXpathItem) {
            $dataNodes[] = $xpath->query(self::rootNode . trim($sourceDetailsXpathItem));
        }

        foreach ($dataNodes as $dataNode) {
            if (is_iterable($dataNode)) {
                foreach ($dataNode as $node) {
                    if ($node->hasChildNodes()) {
                        foreach ($node->childNodes as $n) {
                            $data[] = preg_replace('/\s+/', ' ', $n->textContent);
                        }
                    } else {
                        $data[] = preg_replace('/\s+/', ' ', $node->textContent);
                    }
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
        $xpath = $this->getXpath();
        $personXpath = $this->clientConfigurationManager->getPersonXpath();
        $fisIdentifierXpath =  $this->clientConfigurationManager->getPersonFisIdentifierXpath();
        $personNodes = $xpath->query(self::rootNode . $personXpath);
        $identifiers = [];
        foreach ($personNodes as $key => $node) {
            $identifierNodes = $xpath->query($fisIdentifierXpath, $node);
            if ($identifierNodes->length > 0) {
                $identifiers[] = $identifierNodes->item(0)->nodeValue;
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
        $notesXpath = $this->clientConfigurationManager->getAllNotesXpath();

        $xpath = $this->getXpath();
        $notesNodes = $xpath->query(self::rootNode . $notesXpath);

        $notes = array();

        for ($i=0; $i < $notesNodes->length; $i++)
        {
            $notes[] = $notesNodes->item($i)->nodeValue;
        }

        return $notes;
    }

    public function addNote($noteContent)
    {
        $notesXpath = $this->clientConfigurationManager->getPrivateNotesXpath();

        $parserGenerator = new ParserGenerator($this->clientPid);
        $parserGenerator->setXml($this->xml->saveXML());
        $parserGenerator->customXPath($notesXpath,true, $noteContent);
        $this->xml = new \DOMDocument();
        $this->xml->loadXML($parserGenerator->getXMLData());
    }

    public function getAuthors()
    {
        return $this->getPersons($this->clientConfigurationManager->getPersonAuthorRole());
    }

    public function getPublishers()
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
        $personXpath = $this->clientConfigurationManager->getPersonXpath();
        $familyXpath = $this->clientConfigurationManager->getPersonFamilyXpath();
        $givenXpath = $this->clientConfigurationManager->getPersonGivenXpath();
        $roleXpath = $this->clientConfigurationManager->getPersonRoleXpath();
        $fisIdentifierXpath =  $this->clientConfigurationManager->getPersonFisIdentifierXpath();
        $affiliationXpath =  $this->clientConfigurationManager->getPersonAffiliationXpath();
        $affiliationIdentifierXpath =  $this->clientConfigurationManager->getPersonAffiliationIdentifierXpath();

        $xpath = $this->getXpath();
        $personNodes = $xpath->query(self::rootNode . $personXpath);

        $persons = [];

        foreach ($personNodes as $key => $personNode) {
            $familyNodes = $xpath->query($familyXpath, $personNode);
            $givenNodes = $xpath->query($givenXpath, $personNode);
            $roleNodes = $xpath->query($roleXpath, $personNode);
            $identifierNodes = $xpath->query($fisIdentifierXpath, $personNode);
            $affiliationNodes = $xpath->query($affiliationXpath, $personNode);
            $affiliationIdentifierNodes = $xpath->query($affiliationIdentifierXpath, $personNode);

            $person['affiliations'] = [];
            foreach ($affiliationNodes as $key => $affiliationNode) {
                $person['affiliations'][] = $affiliationNode->nodeValue;
            }

            $person['affiliationIdentifiers'] = [];
            foreach ($affiliationIdentifierNodes as $key => $affiliationIdentifierNode) {
                $person['affiliationIdentifiers'][] = $affiliationIdentifierNode->nodeValue;
            }

            $given = '';
            $family = '';

            if ($givenNodes->length > 0) {
                $given = $givenNodes->item(0)->nodeValue;
            }

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
            if ($roleNodes->length > 0) {
                $person['role'] = $roleNodes->item(0)->nodeValue;
            }

            $person['fobId'] = '';
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
     * @return bool
     */
    public function getValidation()
    {
        $validationXpath =  $this->clientConfigurationManager->getValidationXpath();
        $validation = $this->getValue($validationXpath);
        return (strtolower($validation) === 'true')? true : false;
    }

    /**
     * @param bool $validated
     */
    public function setValidation($validated)
    {
        $validationXpath =  $this->clientConfigurationManager->getValidationXpath();
        $this->setValue($validationXpath, ($validated? 'true' : 'false'));
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
     * @param string $xpathString
     * @return string
     */
    protected function getValue($xpathString)
    {
        $xpath = $this->getXpath();
        $nodeList = $xpath->query(self::rootNode . $xpathString);
        if ($nodeList->length > 0) {
            return $nodeList->item(0)->nodeValue;
        }
        return '';
    }

    /**
     * @param string $xpathString
     * @param string $value
     */
    protected function setValue($xpathString, $value)
    {
        $xpath = $this->getXpath();
        $nodes = $xpath->query(self::rootNode . $xpathString);
        if ($nodes->length > 0) {
            $nodes->item(0)->nodeValue = $value;
        } elseif(!empty($value)) {
            $parserGenerator = new ParserGenerator($this->clientPid);
            $parserGenerator->setXml($this->xml->saveXML());
            $parserGenerator->customXPath($xpathString,true, $value);
            $this->xml = new \DOMDocument();
            $this->xml->loadXML($parserGenerator->getXMLData());
        }
    }

}
