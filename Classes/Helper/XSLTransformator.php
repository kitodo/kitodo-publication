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
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Repository\DocumentTypeRepository;
use EWW\Dpf\Services\Transformer\DocumentTransformer;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Core\Log\LogLevel;
use \TYPO3\CMS\Core\Log\LogManager;
use Exception;


class XSLTransformator
{

    /**
     * clientConfigurationManager
     *
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $clientConfigurationManager;

    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentTypeRepository;

    /**
     * @param $xml
     * @return string
     */
    public function transformInputXML($xml) {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $this->clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);
        $this->documentTypeRepository = $objectManager->get(DocumentTypeRepository::class);

        $domDocument = new \DOMDocument();
        $domDocument->loadXML($xml);

        $domXPath = \EWW\Dpf\Helper\XPath::create($domDocument);

        $docTypeInput = $this->clientConfigurationManager->getTypeXpathInput();

        $documentTypeName = $domXPath->query('//' . $docTypeInput)->item(0)->nodeValue;

        $documentType = $this->documentTypeRepository->findOneByName($documentTypeName);

        if ($documentType == null) {
            throw new Exception("Unknown document type `" . $documentTypeName . "` in given XML."
                . " Requested XPath was `" . $docTypeInput . "`");
        }

        $transformationFile = $documentType->getTransformationFileInput()->current();
        if (!$transformationFile) {
            $transformationFile = $this->clientConfigurationManager->getInputTransformation();
        }

        if ($transformationFile != NULL) {
            $filePath = $transformationFile->getFile()->getOriginalResource()->getIdentifier();
            $documentTransformer = new DocumentTransformer();

            $transformedXml = $documentTransformer->transform(
                Environment::getPublicPath() . '/fileadmin' . $filePath, $xml
            );
        } else {
            // return generated xml if no transformation file is present
            $transformedXml = $xml;

            /** @var $logger Logger */
            $logger = GeneralUtility::makeInstance(
                LogManager::class)->getLogger(__CLASS__
            );

            $logger->log(
                LogLevel::WARNING,
                "Input XML: No transformation file is present. The given xml data was taken over as it is",
                array(
                    'documentTypeName' => $documentTypeName
                )
            );
        }

        return $transformedXml;
    }

    /**
     * @param Document $document
     * @param string $xmlData
     * @return string The transformed xml
     */
    public function getTransformedOutputXML(Document $document, $xmlData = null): string
    {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $this->clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);

        $ownerId = $this->clientConfigurationManager->getOwnerId();
        $documentType = $document->getDocumentType();

        $transformationFile = $documentType->getTransformationFileOutput()->toArray()[0];
        if (!$transformationFile) {
            $transformationFile = $this->clientConfigurationManager->getOutputTransformation();
        }

        if ($transformationFile != NULL) {
            $filePath = $transformationFile->getFile()->getOriginalResource()->getIdentifier();
            $documentTransformer = new DocumentTransformer();

            if ( !$document->getRemoteState() || $document->getRemoteState() == 'NONE' ) {
                $remoteState = 'ACTIVE';
            } else {
                $remoteState = $document->getRemoteState();
            }

            $transformParams = [
                'record_state' => $remoteState,
                'agent_name' => $ownerId,
                'document_type' => $document->getDocumentType()->getName(),
                'process_number' => $document->getProcessNumber(),
                'collection_namespace' => $this->clientConfigurationManager->getFedraCollectionNamespace(),
                'date_issued' => $document->getDateIssued(),
            ];

            if ($xmlData) {
                $transformedXml = $documentTransformer->transform(
                    Environment::getPublicPath() . '/fileadmin' . $filePath, $xmlData, $transformParams
                );
            } else {
                $transformedXml = $documentTransformer->transform(
                    Environment::getPublicPath(). '/fileadmin' . $filePath, $document->publicXml(), $transformParams
                );
            }

        } else {
            // return generated xml if no transformation file is present
            $transformedXml = $document->publicXml();

            /** @var $logger Logger */
            $logger = GeneralUtility::makeInstance(
                LogManager::class)->getLogger(__CLASS__
            );

            $logger->log(
                LogLevel::WARNING,
                "Output XML: No transformation file is present. The generated xml data was taken over as it is",
                array(
                    'documentTypeName' => $documentTypeName
                )
            );
        }

        return $transformedXml;
    }

}
