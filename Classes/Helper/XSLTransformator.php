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
use EWW\Dpf\Services\Transformer\DocumentTransformer;

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
     * @param $xml
     * @return string
     */
    public function transformInputXML($xml) {
        $docTypeInput = $this->clientConfigurationManager->getTypeXpathInput();

        $domDocument = new \DOMDocument();
        $domDocument->loadXML($xml);

        $domXPath = \EWW\Dpf\Helper\XPath::create($domDocument);

        $domXPath->registerNamespace('mods', "http://www.loc.gov/mods/v3");
        $domXPath->registerNamespace('slub', "http://slub-dresden.de/");
        $domXPath->registerNamespace('foaf', "http://xmlns.com/foaf/0.1/");
        $domXPath->registerNamespace('person', "http://www.w3.org/ns/person#");
        $domXPath->registerNamespace('rdf', "http://www.w3.org/1999/02/22-rdf-syntax-ns#");

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
                'agent_name' => 'TEST',
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
