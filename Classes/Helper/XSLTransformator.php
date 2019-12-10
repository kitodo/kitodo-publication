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
use EWW\Dpf\Domain\Repository\DocumentTypeRepository;
use EWW\Dpf\Services\Transformer\DocumentTransformer;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class XSLTransformator
{

    /**
     * clientConfigurationManager
     *
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     * @inject
     */
    protected $clientConfigurationManager;

    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @inject
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

        $docTypeInput = $this->clientConfigurationManager->getTypeXpathInput();

        $domDocument = new \DOMDocument();
        $domDocument->loadXML($xml);

        $domXPath = \EWW\Dpf\Helper\XPath::create($domDocument);

        $documentTypeName = $domXPath->query('//' . $docTypeInput)->item(0)->nodeValue;

        $documentType = $this->documentTypeRepository->findOneByName($documentTypeName);

        $transformationFile = $documentType->getTransformationFileInput()->current();
        if ($transformationFile != NULL) {
            $filePath = $transformationFile->getFile()->getOriginalResource()->getIdentifier();
            $documentTransformer = new DocumentTransformer();

            $transformedXml = $documentTransformer->transform(PATH_site . 'fileadmin' . $filePath, $xml);
        } else {
            // return generated xml if no transformation file is present
            $transformedXml = $xml;
        }

        return $transformedXml;
    }

    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return string The transformed xml
     */
    public function getTransformedOutputXML($document, $xmlData = false)
    {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $this->clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);

        $ownerId = $this->clientConfigurationManager->getOwnerId();
        $documentType = $document->getDocumentType();
        $transformationFile = $documentType->getTransformationFileOutput()->toArray()[0];
        if ($transformationFile != NULL) {
            $filePath = $transformationFile->getFile()->getOriginalResource()->getIdentifier();
            $documentTransformer = new DocumentTransformer();

            if (!$document->getRemoteStatus()) {
                $remoteState = 'ACTIVE';
            } else {
                $remoteState = $document->getRemoteStatus();
            }

            $transformParams = [
                'record_state' => $remoteState,
                'owner_id' => $ownerId,
                'document_type' => $document->getDocumentType()->getName(),
                'process_number' => $document->getProcessNumber()
            ];

            if ($xmlData) {
                $transformedXml = $documentTransformer->transform(PATH_site . 'fileadmin' . $filePath, $xmlData, $transformParams);
            } else {
                $transformedXml = $documentTransformer->transform(PATH_site . 'fileadmin' . $filePath, $document->getXmlData(), $transformParams);
            }

        } else {
            // return generated xml if no transformation file is present
            $transformedXml = $this->getXMLData();
        }

        return $transformedXml;
    }

}
