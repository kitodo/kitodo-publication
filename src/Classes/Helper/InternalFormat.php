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
            $this->xml = new \DOMDocument();
            $this->xml->loadXML($parserGenerator->getXMLData());
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
        $urnXpath = '/mods:mods/mods:identifier[@type="urn"]';

        $qucosaUrnXpath = $this->clientConfigurationManager->getQucosaUrnXpath();
        $qucosaUrnXpath = '/mods:mods/mods:identifier[@type="qucosa:urn"]';

        $urnNodes = $xpath->query(self::rootNode . $urnXpath);
        foreach ($urnNodes as $urnNode) {
            $urnNode->parentNode->removeChild($urnNode);
        }

        $qucosaUrnNodes = $xpath->query(self::rootNode . $qucosaUrnXpath);
        foreach ($qucosaUrnNodes as $qucosaUrnNode) {
            $qucosaUrnNode->parentNode->removeChild($qucosaUrnNode);
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
        //$xpath = $this->clientConfigurationManager->getCreatorXpath();
        $xpath = "/slub:info/slub:creator";
        return $this->getValue($xpath);
    }

    public function setCreator($creator)
    {
        //$xpath = $this->clientConfigurationManager->getCreatorXpath();
        $xpath = "/slub:info/slub:creator";
        $this->setValue($xpath, $creator);
    }

    public function getCreationDate()
    {
        //$xpath = $this->clientConfigurationManager->getCreationDateXpath();
        $xpath = "/slub:info/slub:creationDate";
        return $this->getValue($xpath);
    }

    public function setCreationDate($creationDate)
    {
        //$xpath = $this->clientConfigurationManager->getCreationDateXpath();
        $xpath = "/slub:info/slub:creationDate";
        $this->setValue($xpath, $creationDate);
    }

    public function getRepositoryCreationDate()
    {
        //$xpath = $this->clientConfigurationManager->getRepositoryCreationDateXpath();
        $xpath = "/repositoyCreationDate";
        return $this->getValue($xpath);
    }

    public function getRepositoryLastModDate()
    {
        //$xpath = $this->clientConfigurationManager->getRepositoryLastModDateXpath();
        $xpath = "/repositoyLastModDate";
        return $this->getValue($xpath);
    }

    public function getPublishingYear()
    {
        //$xpath = $this->clientConfigurationManager->getRepositoryPublishingYearXpath();
        $xpath = '/mods:mods/mods:originInfo[@eventType="publication"]/mods:dateIssued[@encoding="iso8601"]';
        return $this->getValue($xpath);
    }

    public function getOriginalSourceTitle()
    {
        //$xpath = $this->clientConfigurationManager->getRepositoryPublishingYearXpath();
        $xpath = '/mods:mods/mods:relatedItem[@type="original"]/mods:titleInfo/mods:title';
        return $this->getValue($xpath);
    }

    /**
     * @return string
     */
    public function getSourceDetails()
    {
        // todo: find a better and confgurable solution, needed for indexing only

        $xpath = $this->getXpath();

        $data = [];

        $dataNodes[] = $xpath->query(self::rootNode . '/mods:mods/mods:relatedItem[@type="original"]');
        $dataNodes[] = $xpath->query(self::rootNode . '/mods:mods/mods:part[@type="article"]/mods:detail/mods:number');
        $dataNodes[] = $xpath->query(self::rootNode . '/mods:mods/mods:part[@type="section"]');

        foreach ($dataNodes as $dataNode) {
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

        $output = trim(implode(' ', $data));
        $output = preg_replace('/\s+/ ', ' ', $output);
        return $output;
    }

    /**
     * Get all related FOB-IDs
     *
     * @return array
     */
    public function getFobIdentifiers(): array
    {
        $xpath = $this->getXpath();

        //$personXpath = $this->clientConfigurationManager->getPersonXpath();
        $personXpath = '/mods:mods/mods:name[@type="personal"]';

        //$fobIdentifierXpath =  $this->clientConfigurationManager->getFobIdentifierXpath();
        $fobIdentifierXpath = 'mods:nameIdentifier[@type="FOBID"]';

        $personNodes = $xpath->query(self::rootNode . $personXpath);
        $identifiers = [];
        foreach ($personNodes as $key => $node) {
            $identifierNodes = $xpath->query($fobIdentifierXpath, $node);
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
        //$depositLicenseXpath = $this->clientConfigurationManager->getDepositLicenseXpath();
        $depositLicenseXpath = '/slub:info/slub:rights/slub:agreement/@given';
        return $this->getValue($depositLicenseXpath);
    }

    /**
     * @return array
     */
    public function getNotes()
    {
        //$notesXpath = $this->clientConfigurationManager->getNotesXpath();
        $notesXpath = "/slub:info/slub:note";

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
        //$notesXpath = $this->clientConfigurationManager->getNotesXpath();
        $notesXpath = '/slub:info/slub:note[@type="private"]';

        $rootNode = $this->getDocument()->documentElement;

        if ($rootNode) {
            $document = $this->getDocument();
            $xpathExplode = array_reverse(explode("/", $notesXpath));
            $i = 1;
            $newElement = null;
            foreach ($xpathExplode as $element) {
                if ($i == 1) {
                    $newElement = $document->createElement($element);
                    $newElement->nodeValue = $noteContent;
                } else {
                    $parentElement = $document->createElement($element);
                    $parentElement->appendChild($newElement);
                    $newElement = $parentElement;
                }
                $i++;
            }
            $rootNode->appendChild($newElement);
        } else {
            throw new \Exception('Invalid xml data.');
        }
    }

    public function getAuthors()
    {
        return $this->getPersons("aut");
    }

    public function getPublishers()
    {
        return $this->getPersons("edt");
    }

    /**
     * Get persons of the given role
     *
     * @param string $role
     * @return array
     */
    public function getPersons($role = '')
    {
        //$personXpath = $this->clientConfigurationManager->getPersonXpath();
        //$personXpath = $this->clientConfigurationManager->getAuthorXpath();
        $personXpath = '/mods:mods/mods:name[@type="personal"]';

        $familyXpath = 'mods:namePart[@type="family"]';

        $givenXpath = 'mods:namePart[@type="given"]';

        $roleXpath = 'mods:role/mods:roleTerm[@type="code"]';

        //$fobIdentifierXpath =  $this->clientConfigurationManager->getFobIdentifierXpath();
        $fobIdentifierXpath = 'mods:nameIdentifier[@type="FOBID"]';

        //$affiliationXpath =  $this->clientConfigurationManager->getAffiliationIdentifierXpath();
        $affiliationXpath  = 'mods:affiliation';

        //$affiliationIdentifierXpath =  $this->clientConfigurationManager->getAffiliationIdentifierXpath();
        $affiliationIdentifierXpath = 'mods:nameIdentifier[@type="ScopusAuthorID"][@typeURI="http://www.scopus.com/authid"]';


        $xpath = $this->getXpath();
        $personNodes = $xpath->query(self::rootNode . $personXpath);

        $persons = [];

        foreach ($personNodes as $key => $personNode) {
            $familyNodes = $xpath->query($familyXpath, $personNode);
            $givenNodes = $xpath->query($givenXpath, $personNode);
            $roleNodes = $xpath->query($roleXpath, $personNode);
            $identifierNodes = $xpath->query($fobIdentifierXpath, $personNode);
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
        //$validationXpath =  $this->clientConfigurationManager->getValidationXpath();
        $validationXpath = "/slub:info/slub:validation/slub:validated";
        $validation = $this->getValue($validationXpath);
        return (strtolower($validation) === 'true')? true : false;
    }

    /**
     * @param bool $validated
     */
    public function setValidation($validated)
    {
        //$validationXpath =  $this->clientConfigurationManager->getValidationXpath();
        $validationXpath = "/slub:info/slub:validation/slub:validated";
        $this->setValue($validationXpath, ($validated? 'true' : 'false'));
    }

    /**
     * @param string $fisId
     */
    public function setFisId($fisId)
    {
        //$fisIdXpath =  $this->clientConfigurationManager->getFisIdXpath();
        $fisIdXpath = "/slub:info/slub:fisId";
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
        return $nodeList->item(0)->nodeValue;
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
        } else {
            $parserGenerator = new ParserGenerator();
            $parserGenerator->setXml($this->xml->saveXML());
            $parserGenerator->customXPath($xpathString,true, $value);
            $this->xml = new \DOMDocument();
            $this->xml->loadXML($parserGenerator->getXMLData());
        }
    }

}
