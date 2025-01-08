<?php

namespace EWW\Dpf\Services\Api;

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

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use EWW\Dpf\Configuration\ClientConfigurationManager;
use EWW\Dpf\Domain\Model\File;
use EWW\Dpf\Services\Storage\FileId;
use EWW\Dpf\Services\Xml\ParserGenerator;
use EWW\Dpf\Services\Xml\XMLFragmentGenerator;
use EWW\Dpf\Services\Xml\XPath;
use Exception;
use phpDocumentor\Reflection\Types\Boolean;
use phpDocumentor\Reflection\Types\String_;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class InternalFormat
{
    const rootNode = '//data/';

    const VALUE_TRUE = 'true';
    const VALUE_FALSE = 'false';
    const VALUE_UNKNOWN = 'unknown';

    /**
     * clientConfigurationManager
     *
     * @var ClientConfigurationManager
     */
    protected $clientConfigurationManager;

    /**
     * xml
     *
     * @var DOMDocument
     */
    protected $xml;

    /**
     * @var int
     */
    protected $clientPid = 0;
    /**
     * @var DOMXPath
     */
    private $preconfiguredDomXPath;

    /**
     * logger
     *
     * @var Logger
     */
    protected $logger = null;

    /**
     * InternalFormat constructor.
     *
     * @param string $xml
     * @param $clientPid
     * @throws Exception
     */
    public function __construct(string $xml, $clientPid = 0)
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        $this->clientPid = $clientPid;

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);

        if ($clientPid) {
            $this->clientConfigurationManager->switchToClientStorage($clientPid);
        }

        $this->setXml($xml);
    }

    public function getXml()
    {
        return $this->xml->saveXML();
    }

    public function setXml($xml)
    {
        if (empty($xml)) {
            $xml = "<data></data>";
        }

        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $this->xml = $dom;
        $this->preconfiguredDomXPath = XPath::create($this->xml);
    }

    public function getDocument(): DOMDocument
    {
        return $this->xml;
    }

    public function getDocumentType(): string
    {
        $typeXpath = $this->clientConfigurationManager->getTypeXpath();
        return $this->getValue($typeXpath);
    }

    /**
     * Get value from XML using an XPath expression
     *
     * @param string $xpathString XPath literal
     * @param mixed $default Default value when $xpathString is empty or no value could be found. Defaults to empty string.
     * @return mixed Value of the specified XML element
     */
    private function getValue(string $xpathString, $default = '')
    {
        $xpath = $this->getXpath();
        if (empty($xpathString)) {
            return $default;
        }
        $nodeList = $xpath->query(self::rootNode . $xpathString);
        return ($nodeList && $nodeList->length > 0) ? $nodeList->item(0)->nodeValue : $default;
    }

    public function getXpath(): DOMXPath
    {
        if ($this->preconfiguredDomXPath == null) {
            $this->preconfiguredDomXPath = XPath::create($this->xml);
        }
        return $this->preconfiguredDomXPath;
    }

    /**
     * @throws Exception
     */
    public function setDocumentType($type)
    {
        $typeXpath = $this->clientConfigurationManager->getTypeXpath();
        $this->setValue($typeXpath, $type);
    }

    /**
     * @param string $xpathString
     * @param string $value
     * @throws Exception
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
        } elseif ($value !== '') {
            $parserGenerator = new ParserGenerator($this->clientPid);
            $parserGenerator->setDomDocument($this->xml);
            $parserGenerator->customXPath($xpathString, true, $value);
            $xml = new DOMDocument();
            $xml->loadXML($parserGenerator->getXMLData());
            $this->setXml($xml->saveXML());
        }
    }

    public function getRepositoryState(): string
    {
        $stateXpath = $this->clientConfigurationManager->getStateXpath();
        return $this->getValue($stateXpath);
    }

    public function getProcessNumber(): string
    {
        return $this->getValue($this->clientConfigurationManager->getProcessNumberXpath());
    }

    /**
     * @throws Exception
     */
    public function setProcessNumber($processNumber)
    {
        $processNumberXpath = $this->clientConfigurationManager->getProcessNumberXpath();
        $this->setValue($processNumberXpath, $processNumber);
    }

    public function getTitle(): string
    {
        $titleXpath = $this->clientConfigurationManager->getTitleXpath();
        return $this->getValue($titleXpath);
    }

    /**
     *
     *
     * @param string $title
     * @throws Exception
     */
    public function setTitle(string $title)
    {
        $titleXpath = $this->clientConfigurationManager->getTitleXpath();
        $this->setValue($titleXpath, $title);
    }

    /**
     * Gets all file data found in the xml data file node.
     *
     * @return array
     */
    public function getFiles(): array
    {
        $files = [];

        $fileXpath = $this->clientConfigurationManager->getFileXpath();
        if (empty($fileXpath)) {
            return [];
        }

        $xpath = $this->getXpath();
        $fileNodes = $xpath->query(self::rootNode . $fileXpath);

        if (!$fileNodes) {
            return [];
        }

        foreach ($fileNodes as $fileNode) {
            $xpathToAttributeMapping = [
                'archive' => $this->clientConfigurationManager->getFileArchiveXpath(),
                'deleted' => $this->clientConfigurationManager->getFileDeletedXpath(),
                'download' => $this->clientConfigurationManager->getFileDownloadXpath(),
                'href' => $this->clientConfigurationManager->getFileHrefXpath(),
                'id' => $this->clientConfigurationManager->getFileIdXpath(),
                'mimetype' => $this->clientConfigurationManager->getFileMimetypeXpath(),
                'primary' => $this->clientConfigurationManager->getFilePrimaryXpath(),
                'title' => $this->clientConfigurationManager->getFileTitleXpath(),
            ];

            // set types, defaults and matching patterns
            $fileAttributes = [
                'archive' => [Boolean::class, false],
                'deleted' => [Boolean::class, false],
                'download' => [Boolean::class, false],
                'href' => [String_::class, ''],
                'id' => [String_::class, ''],
                'mimetype' => [String_::class, ''],
                'primary' => [Boolean::class, false, 'primary'],
                'title' => [String_::class, ''],
            ];

            foreach ($xpathToAttributeMapping as $k => $xp) {
                if (!empty($xp)) {
                    $nl = $xpath->query($xp, $fileNode);
                    $default = $fileAttributes[$k][1];
                    if ($nl->count() == 0) {
                        // nothing found, use default
                        $fileAttributes[$k] = $default;
                    } else {
                        $nodeValue = $nl->item(0)->nodeValue;
                        // found node, check for match
                        $condition = $fileAttributes[$k][2];
                        if (isset($condition)) {
                            $update = ($nodeValue === $condition) ? $nodeValue : $default;
                        } else {
                            // just use node value
                            $update = $nodeValue;
                        }
                        // update value according to type
                        $type = $fileAttributes[$k][0];
                        if ($type == Boolean::class) {
                            $fileAttributes[$k] = boolval($update);
                        } elseif ($type == String_::class) {
                            $fileAttributes[$k] = trim($update);
                        }
                    }
                }
            }

            $files[] = $fileAttributes;
        }
        return $files;
    }

    /**
     *
     *
     * @param string $date
     * @return void
     * @throws Exception
     */
    public function setDateIssued(string $date)
    {
        $dateXpath = $this->clientConfigurationManager->getDateXpath();
        $this->setValue($dateXpath, $date);
    }

    /**
     *
     * @return string
     */
    public function getDateIssued(): string
    {
        $dateXpath = $this->clientConfigurationManager->getDateXpath();
        return $this->getValue($dateXpath);
    }

    /**
     *
     * @return bool
     */
    public function getPrimaryUrn(): bool
    {
        $value = $this->getValue($this->clientConfigurationManager->getPrimaryUrnXpath());
        return !empty($value);
    }

    public function clearAllUrn()
    {
        $xpath = $this->getXpath();
        $urnXpath = $this->clientConfigurationManager->getUrnXpath();
        if (empty($urnXpath)) {
            return;
        }

        $urnNodes = $xpath->query(self::rootNode . $urnXpath);
        if ($urnNodes) {
            foreach ($urnNodes as $urnNode) {
                $urnNode->parentNode->removeChild($urnNode);
            }
        }

        $primaryUrnXpath = $this->clientConfigurationManager->getPrimaryUrnXpath();
        if (empty($primaryUrnXpath)) {
            return;
        }

        $primaryUrnNodes = $xpath->query(self::rootNode . $primaryUrnXpath);
        if ($primaryUrnNodes) {
            foreach ($primaryUrnNodes as $primaryUrnNode) {
                $primaryUrnNode->parentNode->removeChild($primaryUrnNode);
            }
        }
    }

    public function getSubmitterEmail(): string
    {
        return $this->getValue($this->clientConfigurationManager->getSubmitterEmailXpath());
    }

    public function getSubmitterName(): string
    {
        return $this->getValue($this->clientConfigurationManager->getSubmitterNameXpath());
    }

    public function getSubmitterNotice(): string
    {
        return $this->getValue($this->clientConfigurationManager->getSubmitterNoticeXpath());
    }

    /**
     * @return int
     */
    public function getCreator(): int
    {
        $creatorXpath = $this->clientConfigurationManager->getCreatorXpath();
        return intval($this->getValue($creatorXpath, 0));
    }

    /**
     *
     * @param string $creator
     * @throws Exception
     */
    public function setCreator(string $creator)
    {
        $creatorXpath = $this->clientConfigurationManager->getCreatorXpath();
        $this->setValue($creatorXpath, $creator);
    }

    public function getCreationDate(): string
    {
        $xpath = $this->clientConfigurationManager->getCreationDateXpath();
        return $this->getValue($xpath);
    }

    /**
     * @throws Exception
     */
    public function setCreationDate($creationDate)
    {
        $xpath = $this->clientConfigurationManager->getCreationDateXpath();
        $this->setValue($xpath, $creationDate);
    }

    public function getRepositoryCreationDate(): string
    {
        $xpath = $this->clientConfigurationManager->getRepositoryCreationDateXpath();
        return $this->getValue($xpath);
    }

    public function getRepositoryLastModDate(): string
    {
        $xpath = $this->clientConfigurationManager->getRepositoryLastModDateXpath();
        return $this->getValue($xpath);
    }

    /**
     * @return array
     */
    public function getSearchYear(): array
    {
        $yearXpath = $this->clientConfigurationManager->getSearchYearXpaths();
        $yearXpathList = explode(";", trim($yearXpath, " ;"));
        $xpath = $this->getXpath();
        $values = [];

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
    public function getSearchTitles(): array
    {
        $searchTitleXpaths = $this->clientConfigurationManager->getAdditionalSearchTitleXpaths();
        $searchTitleXpathList = explode(";", trim($searchTitleXpaths, " ;"));
        $xpath = $this->getXpath();
        $values = [];

        foreach ($searchTitleXpathList as $searchTitleXpathItem) {
            if (empty($searchTitleXpathItem)) continue;

            $elements = $xpath->query(self::rootNode . trim($searchTitleXpathItem));

            if ($elements) foreach ($elements as $element) {
                $values[] = trim($element->nodeValue);
            }
        }

        return $values;
    }

    /**
     * @return array
     */
    public function getSearchIdentifiers(): array
    {
        $additionalIdentifierXpaths = $this->clientConfigurationManager->getAdditionalIdentifierXpaths();
        $additionalIdentifierXpathList = explode(";", trim($additionalIdentifierXpaths, " ;"));
        $xpath = $this->getXpath();
        $values = [];

        foreach ($additionalIdentifierXpathList as $additionalIdentifierXpathItem) {
            if (empty($additionalIdentifierXpathItem)) continue;

            $elements = $xpath->query(self::rootNode . trim($additionalIdentifierXpathItem));

            if ($elements) foreach ($elements as $element) {
                $values[] = trim($element->nodeValue);
            }
        }

        return $values;
    }

    /**
     * @return array
     */
    public function getSearchLanguage(): array
    {
        $searchLanguageXpaths = $this->clientConfigurationManager->getSearchLanguageXpaths();
        $searchLanguageXpathList = explode(";", trim($searchLanguageXpaths, " ;"));
        $xpath = $this->getXpath();
        $values = [];

        foreach ($searchLanguageXpathList as $searchLanguageXpathItem) {
            if (empty($searchLanguageXpathItem)) continue;

            $elements = $xpath->query(self::rootNode . trim($searchLanguageXpathItem));

            if ($elements) foreach ($elements as $element) {
                $values[] = trim($element->nodeValue);
            }
        }

        return $values;
    }

    /**
     * @return array
     */
    public function getPublishers(): array
    {
        $publisherXpath = $this->clientConfigurationManager->getPublisherXpaths();
        $publisherXpathList = explode(";", trim($publisherXpath, " ;"));
        $xpath = $this->getXpath();
        $values = [];

        foreach ($publisherXpathList as $publisherXpathItem) {
            if (empty($publisherXpathItem)) continue;

            $elements = $xpath->query(self::rootNode . trim($publisherXpathItem));
            if ($elements) foreach ($elements as $element) {
                $values[] = trim($element->nodeValue);
            }
        }

        return $values;
    }

    public function getOriginalSourceTitle(): string
    {
        $originalSourceTitleXpath = $this->clientConfigurationManager->getOriginalSourceTitleXpath();
        return $this->getValue($originalSourceTitleXpath);
    }

    /**
     * @return string
     */
    public function getSourceDetails(): string
    {
        $xpath = $this->getXpath();
        $data = [];
        $sourceDetailsXpaths = $this->clientConfigurationManager->getSourceDetailsXpaths();

        if (empty($sourceDetailsXpaths)) {
            return '';
        }

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
        return preg_replace('/\s+/', ' ', $output);
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

            $fisIdentifierXpath = $this->clientConfigurationManager->getPersonFisIdentifierXpath();
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
    public function getDepositLicense(): string
    {
        $depositLicenseXpath = $this->clientConfigurationManager->getDepositLicenseXpath();
        return $this->getValue($depositLicenseXpath);
    }

    /**
     * @return array
     */
    public function getNotes(): array
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

    /**
     * @throws Exception
     */
    public function addNote($noteContent)
    {
        $notesXpath = $this->clientConfigurationManager->getPrivateNotesXpath();
        $this->setValue($notesXpath, $noteContent);
    }

    public function getAuthors()
    {
        return $this->getPersons($this->clientConfigurationManager->getPersonAuthorRole());
    }

    /**
     * Get persons of the given role
     *
     * @param string $role
     * @return array
     */
    public function getPersons(string $role = ''): array
    {
        $persons = [];

        $personXpath = $this->clientConfigurationManager->getPersonXpath();

        if (empty($personXpath)) return [];

        $xpath = $this->getXpath();
        $personNodes = $xpath->query(self::rootNode . $personXpath);

        $familyXpath = $this->clientConfigurationManager->getPersonFamilyXpath();
        $givenXpath = $this->clientConfigurationManager->getPersonGivenXpath();
        $roleXpath = $this->clientConfigurationManager->getPersonRoleXpath();
        $fisIdentifierXpath = $this->clientConfigurationManager->getPersonFisIdentifierXpath();
        $affiliationXpath = $this->clientConfigurationManager->getPersonAffiliationXpath();
        $affiliationIdentifierXpath = $this->clientConfigurationManager->getPersonAffiliationIdentifierXpath();

        if ($personNodes) foreach ($personNodes as $key => $personNode) {
            $person['affiliations'] = [];
            $affiliationNodes = empty($affiliationXpath) ? [] : $xpath->query($affiliationXpath, $personNode);
            foreach ($affiliationNodes as $affiliationNode) {
                $person['affiliations'][] = $affiliationNode->nodeValue;
            }

            $person['affiliationIdentifiers'] = [];
            $affiliationIdentifierNodes = empty($affiliationIdentifierXpath) ? [] : $xpath->query($affiliationIdentifierXpath, $personNode);
            if ($affiliationIdentifierNodes) foreach ($affiliationIdentifierNodes as $affiliationIdentifierNode) {
                $person['affiliationIdentifiers'][] = $affiliationIdentifierNode->nodeValue;
            }

            $given = '';
            $family = '';
            $givenNodes = empty($givenXpath) ? [] : $xpath->query($givenXpath, $personNode);
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
     * Get project data for the index
     *
     * @return array
     */
    public function getProjects(): array
    {
        $xpath = $this->getXpath();
        $projects = [];

        $projectIdXpath = $this->clientConfigurationManager->getProjectIdXpath();
        $projectTitleXpath = $this->clientConfigurationManager->getProjectTitleXpath();

        if (! empty($projectIdXpath)) {
            try {
                $idNodes = $xpath->query(self::rootNode . $projectIdXpath);
            } catch (\Throwable $throwable) {
                $this->logger->log(LogLevel::ERROR, 'Invalid client configuration for ProjectIdXpath.');
            }
            if ($idNodes->length > 0) {
                foreach ($idNodes as $idNode) {
                    $projects[] = $idNode->nodeValue;
                }
            }
        } else {
            $this->logger->log(LogLevel::ERROR, 'Missing client configuration for ProjectIdXpath.');
        }

        if (! empty($projectTitleXpath)) {
            try {
                $projectTitleXpath = array_map('trim', explode('|', $projectTitleXpath));
            } catch (\Throwable $throwable) {
                $this->logger->log(LogLevel::ERROR, 'Invalid client configuration for ProjectTitleXpath.');
            }
            foreach ($projectTitleXpath as $titleXpath) {
                $titleNodes = $xpath->query(self::rootNode . $titleXpath);
                if ($titleNodes->length > 0) {
                    foreach ($titleNodes as $titleNode) {
                        $projects[] = $titleNode->nodeValue;
                    }
                }
            }
        } else {
            $this->logger->log(LogLevel::ERROR, 'Missing client configuration for ProjectTitleXpath.');
        }

        return $projects;
    }

    /**
     * @param string $fisId
     * @throws Exception
     */
    public function setFisId(string $fisId)
    {
        $fisIdXpath = $this->clientConfigurationManager->getFisIdXpath();
        $this->setValue($fisIdXpath, $fisId);
    }

    /**
     * @return string
     */
    public function getFisId(): string
    {
        $fisIdXpath = $this->clientConfigurationManager->getFisIdXpath();
        return $this->getValue($fisIdXpath);
    }

    /**
     * @return array
     */
    public function getCollections(): array
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
    public function getTextType(): string
    {
        $textTypeXpath = $this->clientConfigurationManager->getTextTypeXpath();
        return $this->getValue($textTypeXpath);
    }

    /**
     * @return string
     */
    public function getOpenAccessForSearch(): string
    {
        $openAccessXpaths = $this->clientConfigurationManager->getOpenAccessXpath();

        if ($openAccessXpaths !== '') {
            $xpath            = $this->getXpath();
            $openAccessValues = $this->clientConfigurationManager->getOpenAccessValues();

            $openAccessXpathList = explode(";", trim($openAccessXpaths, " ;"));

            foreach ($openAccessXpathList as $openAccessXpathItem) {
                if (empty($openAccessXpathItem)) continue;

                $openAccessElements = $xpath->query(self::rootNode . trim($openAccessXpathItem));

                if ($openAccessElements) {
                    foreach ($openAccessElements as $element) {
                        if (
                            strtolower(trim($element->nodeValue)) === strtolower($openAccessValues['true']) ||
                            strtolower(trim($element->nodeValue)) === strtolower($openAccessValues['trueUri'])
                        ) {
                            return self::VALUE_TRUE;
                        }
                    }
                }

            }
        }
        return self::VALUE_FALSE;
    }

    /**
     * @return string
     */
    public function getPeerReviewForSearch(): string
    {
        $xpath = $this->getXpath();

        $peerReviewOtherVersionXpath = $this->clientConfigurationManager->getPeerReviewOtherVersionXpath();

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
    public function getLicense(): string
    {
        $licenseXpath = $this->clientConfigurationManager->getLicenseXpath();
        return $this->getValue($licenseXpath);
    }

    /**
     * @return string
     */
    public function getFrameworkAgreementId(): string
    {
        $xpath = $this->clientConfigurationManager->getFrameworkAgreementIdXpath();
        return $this->getValue($xpath);
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
     * @param ObjectStorage<File> $files
     * @throws Exception
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
        foreach ($files->toArray() as $file) {
            $dataStreamIdentifier = $file->getDatastreamIdentifier();

            if ($file->isFileGroupDeleted()) {
                continue;
            }

            if ($file->isDeleted()) {
                if (!empty($dataStreamIdentifier)) {
                    $fragment = XMLFragmentGenerator::fragmentFor($fileXpath);

                    // FIXME Why are we updating all the fields on delete?
                    libxml_use_internal_errors(true);
                    $dom = new DOMDocument();
                    $domLoaded = $dom->loadXML($fragment);
                    libxml_use_internal_errors(false);

                    if ($domLoaded) {
                        /** @var DOMElement $newFile */
                        $newFile = $this->xml->importNode($dom->firstChild, true);
                        $newFile->setAttribute('usage', 'delete');
                        $this->updateChildNode($newFile, $idXpath, $file->getDatastreamIdentifier());
                        $this->updateChildNode($newFile, $hrefXpath, $file->getLink());
                        $this->updateChildNode($newFile, $titleXpath, $file->getLabel());
                        $this->updateChildNode($newFile, $deletedXpath, 'yes');
                        $this->updateChildNode($newFile, $archiveXpath, $file->getArchive());
                        $this->updateChildNode($newFile, $mimeTypeXpath, $file->getContentType());
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
                    $this->updateChildNode($fileNodes->item(0), $idXpath, $fileId->getId($file));
                    $this->updateChildNode($fileNodes->item(0), $mimeTypeXpath, $file->getContentType());
                }
            }
        }
    }

    /**
     * Add or update a child node to a parent node, selected by the given XPath expression.
     *
     * @param DOMNode $parent Parent node
     * @param string $xpath An XPath expression describing the child node
     * @param string $value Value of the newly created node
     */
    public function updateChildNode(DOMNode $parent, string $xpath, string $value)
    {
        if (empty($xpath)) {
            return;
        }

        $nodes = $this->getXpath()->query($xpath, $parent);

        if ($nodes && $nodes->length > 0) {
            $nodes->item(0)->nodeValue = $value;
        } else {
            $fragment = XMLFragmentGenerator::fragmentFor($xpath . "='" . $value . "'");

            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $domLoaded = $dom->loadXML($fragment);
            libxml_use_internal_errors(false);

            if ($domLoaded) {
                $newField = $this->xml->importNode($dom->firstChild, true);
                $parent->appendChild($newField);
            }
        }
    }

    /**
     * @param ObjectStorage<File> $files
     * @throws Exception
     */
    public function updateFileHrefs(ObjectStorage $files)
    {
        $xpath = $this->getXpath();
        $fileXpath = $this->clientConfigurationManager->getFileXpath();
        $idXpath = $this->clientConfigurationManager->getFileIdXpath();
        $fileHrefXpath = $this->clientConfigurationManager->getFileHrefXpath();

        /** @var File $file */
        foreach ($files->toArray() as $file) {

            $fileNodes = $xpath->query(
                self::rootNode . $fileXpath . '[./' . trim($idXpath, '@/ ') . '="' . $file->getFileIdentifier() . '"]'
            );

            if ($fileNodes && $fileNodes->length > 0) {
                $this->updateChildNode($fileNodes->item(0), $fileHrefXpath, $file->getLink());
            }
        }
    }
}
