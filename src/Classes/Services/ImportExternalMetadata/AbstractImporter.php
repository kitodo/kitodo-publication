<?php
namespace EWW\Dpf\Services\ImportExternalMetadata;

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

\Httpful\Bootstrap::init();

use EWW\Dpf\Domain\Model\ExternalMetadata;
use \Httpful\Request;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use EWW\Dpf\Services\Transformer\DocumentTransformer;
use EWW\Dpf\Services\ProcessNumber\ProcessNumberGenerator;
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Model\DocumentType;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Log\LogManager;


abstract class AbstractImporter
{
    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager = null;

    /**
     * clientRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ClientRepository
     * @inject
     */
    protected $clientRepository = null;

    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @inject
     */
    protected $documentTypeRepository = null;

    /**
     * clientConfigurationManager
     *
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     * @inject
     */
    protected $clientConfigurationManager;

    /**
     * security
     *
     * @var \EWW\Dpf\Security\Security
     * @inject
     */
    protected $security = null;

    /**
     * logger
     *
     * @var \TYPO3\CMS\Core\Log\Logger
     */
    protected $logger = null;

    /**
     * @var bool
     */
    protected $generateProcessNumber = true;

    public function __construct()
    {
        /** @var $logger \TYPO3\CMS\Core\Log\Logger */
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * Returns the list of publication types: ( ['type','"type"'] ).
     * @param array $types
     * @return array
     */
    public static function typeItems($types)
    {
        $items = [];

        foreach ($types as $type) {
            $items[] = [
                $type, '"'.$type.'"'
            ];
        }

        return $items;
    }

    /**
     * @param ExternalMetadata $metaData
     * @param DocumentType $documentType
     * @return Document
     * @throws \EWW\Dpf\Exceptions\DocumentMaxSizeErrorException
     */
    public function import($metadata, $documentType = null)
    {
        $publicationType = $metadata->getPublicationType();

        if (empty($documentType)) {
            $documentType = $this->determineDocumentType($publicationType);
        }

        $metsXml = $this->transformToMetsXml($metadata->getData(), $documentType);

        if ($metsXml) {
            /** @var Document $document */
            $document = $this->createDocument($metsXml, $documentType);
            $document->setDocumentType($documentType);

            return $document;
        }

        return null;
    }

    /**
     * @param string $xml
     * @param DocumentType $documentType
     * @return string|null $metadataXml
     */
    public function transformToMetsXml($xml, $documentType)
    {
        /** @var DocumentTransformer $documentTransformer */
        $documentTransformer = new DocumentTransformer();

        $metsXml = '';

        if ($xml && $documentType instanceof DocumentType) {

            // $ownerId is currently not needed for the purpose of importing new documents
            //$ownerId = $this->clientConfigurationManager->getOwnerId();

            $transformParams = [
                'document_type' => $documentType->getName(),
                // Currently not needed parameters for the purpose of importing new documents
                //'record_state' => $remoteState,
                //'owner_id' => $ownerId,
                //'process_number' => $processNumber,
            ];

            $xsltFilePath = $this->getXsltFilePath($documentType);

            if ($xsltFilePath) {
                $metsXml = $documentTransformer->transform($xsltFilePath, $xml, $transformParams);
            }

            return($metsXml);
        }

        return null;
    }

    /**
     * @param string $metsXml
     * @param DocumentType $documentType
     * @return Document
     * @throws \EWW\Dpf\Exceptions\DocumentMaxSizeErrorException
     */
    protected function createDocument($metsXml, $documentType)
    {
        /* @var $newDocument \EWW\Dpf\Domain\Model\Document */
        $newDocument    =  $this->objectManager->get(Document::class);

        $mets = new \EWW\Dpf\Helper\Mets($metsXml);
        $mods = $mets->getMods();
        $slub = $mets->getSlub();

        // xml data fields are limited to 64 KB
        if (strlen($mods->getModsXml()) >= 64 * 1024 || strlen($slub->getSlubXml() >= 64 * 1024)) {
            throw new \EWW\Dpf\Exceptions\DocumentMaxSizeErrorException("Maximum document size exceeded.");
        }

        $title   = $mods->getTitle();
        $authors = $mods->getAuthors();

        $newDocument->setTitle($title);
        $newDocument->setAuthors($authors);
        $newDocument->setDocumentType($documentType);

        $newDocument->setXmlData($mods->getModsXml());
        $newDocument->setSlubInfoData($slub->getSlubXml());
        $newDocument->setCreator($this->security->getUser()->getUid());

        $newDocument->setState(DocumentWorkflow::STATE_NEW_NONE);

        if ($this->generateProcessNumber) {
            $processNumberGenerator = $this->objectManager->get(ProcessNumberGenerator::class);
            $processNumber = $processNumberGenerator->getProcessNumber();
            $newDocument->setProcessNumber($processNumber);
        }

        return $newDocument;
    }

    /**
     * @return \EWW\Dpf\Domain\Model\TransformationFile
     */
    protected abstract function getDefaultXsltTransformation();

    /**
     * @return string
     */
    protected abstract function getDefaultXsltFilePath();

    /**
     * @param DocumentType $documentType
     * @return string
     */
    protected function getXsltFilePath(DocumentType $documentType)
    {
        /** @var \EWW\Dpf\Domain\Model\TransformationFile $xsltTransformationFile */
        $xsltTransformationFile = $documentType->getCrossrefTransformation()->current();

        if (empty($xsltTransformationFile)) {
            $xsltTransformationFile = $this->getDefaultXsltTransformation();
        }

        if ($xsltTransformationFile) {
            return PATH_site . 'fileadmin' . $xsltTransformationFile->getFile()->getOriginalResource()->getIdentifier();
        } else {
            return $this->getDefaultXsltFilePath();
        }

        return '';
    }

    /**
     * @return string
     */
    protected abstract function getImporterName();


    /**
     * @param string $publicationType
     * @return DocumentType|null
     */
    protected function determineDocumentType($publicationType) : ?DocumentType
    {
        $documentType = null;

        // Get the target document type.
        if ($publicationType) {
            $documentType = $this->documentTypeRepository->findOneByExternalType(
                $publicationType, $this->getImporterName().'_types'
            );
        }

        if (!$documentType) {
            $configurationManager = $this->objectManager->get(ConfigurationManager::class);
            $settings = $configurationManager->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS
            );

            if (array_key_exists('defaultImportDocumentType', $settings)) {
                $documentType = $this->documentTypeRepository->findByUid(
                    $settings['defaultImportDocumentType'][$this->getImporterName()]
                );
            }
        }
        
        return $documentType;
    }


    /**
     * Determines whether the identifier is a DOI, ISBN or PMID.
     *
     * @param $identifier
     * @return null|string
     */
    static function determineIdentifierType($identifier)
    {
        // DOI
        if (strpos($identifier,'10.') === 0) {
            return 'DOI';
        }

        // ISBN
        $length = strlen(str_replace(['-',' '], '', $identifier));

        if ($length === 13) {
            if (strpos($identifier, '978') === 0 ||  strpos($identifier, '979') === 0) {
                return 'ISBN';
            }
        }

        if ($length === 10) {
            return 'ISBN';
        }

        $length = strlen(trim($identifier));
        if ($length === 9) {
            if (strpos($identifier, '-') === 4) {
                return 'ISSN';
            }
        }

        // PMID
        if (is_numeric($identifier) && intval($identifier) == $identifier) {
            if (strlen($identifier) < 10) {
                return 'PMID';
            }
        }

        return null;
    }

    public function activateProcessNumberGeneration()
    {
        $this->generateProcessNumber = true;
    }

    public function deactivateProcessNumberGeneration()
    {
        $this->generateProcessNumber = false;
    }
}
