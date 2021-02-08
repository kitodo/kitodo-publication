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

use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Services\ProcessNumber\ProcessNumberGenerator;
use JsonPath\JsonObject;

class JsonToDocumentMapper
{
    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @inject
     */
    protected $objectManager;

    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @inject
     */
    protected $documentTypeRepository = null;

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository = null;

    /**
     * Replaces the data from the document with the data from the json
     * @param Document $document
     * @param $jsonData
     * @return Document
     */
    public function editDocument(Document $document, $jsonData)
    {
        $metaData = $this->getMetadataFromJson($jsonData, $document->getDocumentType());
        $xmlData = $document->getXmlData();

        $domDocument = new \DOMDocument();
        $domDocument->loadXML($xmlData);

        $xpath = \EWW\Dpf\Helper\XPath::create($domDocument);

        foreach ($metaData as $groupKey => $group) {
            $groupMapping = $group['mapping'];
            $groupNode = $xpath->query($groupMapping);
            if ($group['values']) {
                if ($groupNode->length > 0) {
                    foreach ($groupNode as $nodeItem) {
                        $domDocument->documentElement->removeChild($nodeItem);
                    }
                }
            } else {
                foreach ($groupNode as $nodeItem) {
                    $domDocument->documentElement->removeChild($nodeItem);
                }
            }
        }

        foreach ($metaData['mods'] as $groupKey => $group) {
            $groupMapping = $group['mapping'];
            $groupChild = null;
            //if ($group['values']) {
                $parent = $domDocument->childNodes->item(0);
                $path = $this->parseXpathString($groupMapping);
                foreach ($path as $pathItem) {
                    $groupChild = $domDocument->createElement($pathItem['node']);
                    foreach ($pathItem['attributes'] as $attrName => $attrValue) {
                        $attributeElement = $domDocument->createAttribute($attrName);
                        $attributeElement->nodeValue = $attrValue;
                        $groupChild->appendChild($attributeElement);
                    }
                    $parent->appendChild($groupChild);
                    $parent = $groupChild;
                }

                if ($groupChild) {
                    if ($group['values']) {
                        foreach ($group['values'] as $fieldKey => $field) {
                            $parent = $groupChild;
                            $path = $this->parseXpathString($field['mapping']);
                            foreach ($path as $pathItem) {
                                $child = $domDocument->createElement($pathItem['node']);
                                foreach ($pathItem['attributes'] as $attrName => $attrValue) {
                                    $attributeElement = $domDocument->createAttribute($attrName);
                                    $attributeElement->nodeValue = $attrValue;
                                    $child->appendChild($attributeElement);
                                }
                                $parent->appendChild($child);
                                $parent = $child;
                            }
                            if ($field['value']) {
                                $child->nodeValue = $field['value'];
                            }

                        }
                    }
                }
            //}
        }

        $xmlData = $domDocument->saveXML();
        $document->setXmlData($xmlData);

        return $document;
    }

    /**
     * Creates a document from the given json data
     *
     * @param string $jsonData
     * @return Document $document
     */
    public function getDocument($jsonData)
    {
        $jsonObject = new JsonObject($jsonData);
        $publicationType = $jsonObject->get('$.publicationType');

        if ($publicationType && is_array($publicationType)) {
            $publicationType = $publicationType[0];
        }

        $documentType = $this->documentTypeRepository->findOneByName($publicationType);
        if (!$documentType) {
            return null;
        }

        /** @var Document $document */
        $document = $this->objectManager->get(Document::class);

        $document->setDocumentType($documentType);

        $processNumberGenerator = $this->objectManager->get(ProcessNumberGenerator::class);
        $processNumber = $processNumberGenerator->getProcessNumber();
        $document->setProcessNumber($processNumber);

        $metaData = $this->getMetadataFromJson($jsonData);

        $exporter = new \EWW\Dpf\Services\ParserGenerator();

        $documentData['documentUid'] = 0;
        $documentData['metadata']    = $metaData;
        $documentData['files']       = array();

        $exporter->buildXmlFromForm($documentData);

        $internalXml = $exporter->getXMLData();
        $document->setXmlData($internalXml);

        $internalFormat = new \EWW\Dpf\Helper\InternalFormat($internalXml);

        $document->setTitle($internalFormat->getTitle());
        $document->setAuthors($internalFormat->getAuthors());
        $document->setDateIssued($internalFormat->getDateIssued());
        //$document->setEmbargoDate($formMetaData['embargo']);

        $internalFormat->setDocumentType($documentType->getName());
        $internalFormat->setProcessNumber($document->getProcessNumber());

        $document->setXmlData($internalFormat->getXml());

        $document->setState(\EWW\Dpf\Domain\Workflow\DocumentWorkflow::STATE_REGISTERED_NONE);

        return $document;
    }


    public function getMetadataFromJson($jsonData, $documentType = null)
    {
        $jsonData = empty($jsonData)? null: $jsonData;
        $jsonObject = new JsonObject($jsonData);

        if ($documentType) {
            $publicationType = $documentType;
        } else {
            $publicationType = $jsonObject->get('$.publicationType');
            if ($publicationType && is_array($publicationType)) {
                $publicationType = $publicationType[0];
            }

            /** @var \EWW\Dpf\Domain\Model\DocumentType $documentType */
            $documentType = $this->documentTypeRepository->findOneByName($publicationType);
        }

        $resultData = [];

        if (empty($documentType)) {
            // default type
            $documentType = $this->documentTypeRepository->findOneByName('article');
        }

        foreach ($documentType->getMetadataPage() as $metadataPage) {

            foreach ($metadataPage->getMetadataGroup() as $metadataGroup) {

                // Group mapping
                $jsonDataObject = new JsonObject($jsonData);
                $jsonGroupMapping = $metadataGroup->getJsonMapping();
                $groupItems = [];
                if ($jsonGroupMapping) {
                    $groupItems = $jsonDataObject->get($jsonGroupMapping);
                }

                if (empty($groupItems)) {
                    $groupItems = [];
                }

                foreach ($groupItems as $groupItem) {

                    $resultGroup = [
                        'attributes' => [],
                        'values' => []
                    ];
                    $resultGroup['mapping'] = $metadataGroup->getRelativeMapping();
                    $resultGroup['modsExtensionMapping'] = $metadataGroup->getRelativeModsExtensionMapping();
                    $resultGroup['modsExtensionReference'] = trim($metadataGroup->getModsExtensionReference(), " /");
                    $resultGroup['groupUid'] = $metadataGroup->getUid();

                    foreach ($metadataGroup->getMetadataObject() as $metadataObject) {

                        $json = json_encode($groupItem);

                        $jsonObject = new JsonObject($json);

                        $fieldItems = [];
                        $jsonFieldMapping = $metadataObject->getJsonMapping();

                        if ($jsonFieldMapping) {
                            $fieldItems = $jsonObject->get($jsonFieldMapping);
                            if (empty($fieldItems)) {
                                $fieldItems = [];
                            }
                        }

                        foreach ($fieldItems as $fieldItem) {
                            $resultField = [];

                            if (!is_array($fieldItem)) {
                                $value = $fieldItem;
                            } else {
                                $value = implode("; ", $fieldItem);
                            }

                            if ($metadataObject->getDataType() == \EWW\Dpf\Domain\Model\MetadataObject::INPUT_DATA_TYPE_DATE) {
                                $date = date_create_from_format('d.m.Y', trim($value));
                                if ($date) {
                                    $value = date_format($date, 'Y-m-d');
                                }
                            }

                            //if ($value) {
                                $value = str_replace('"', "'", $value);
                                $fieldMapping = $metadataObject->getRelativeMapping();
                                $resultField['modsExtension'] = $metadataObject->getModsExtension();
                                $resultField['mapping'] = $fieldMapping;
                                $resultField['value']   = $value;

                                if (strpos($fieldMapping, "@") === 0) {
                                    $resultGroup['attributes'][] = $resultField;
                                } else {
                                    $resultGroup['values'][] = $resultField;
                                }
                            //}
                        }
                    }

                    $resultData[] = $resultGroup;;
                }

            }
        }

        return $resultData;
    }

    protected function parseXpathString($xpathString)
    {
        $result = [];

        $regex = '/[a-zA-Z:]+|[<=>]|[@][a-zA-Z][a-zA-Z0-9_\-\:\.]*|\[|\'.*?\'|".*?"|\]|\//';
        preg_match_all($regex, $xpathString, $matches);
        $path = [];
        $i = 0;
        foreach ($matches[0] as $item) {
            if ($item != "/") {
                $path[$i] .= $item;
            } else {
                $i++;
            }
        }

        foreach ($path as $key => $pathItem) {
            $nodeName = explode("[", $pathItem);
            $result[$key]["node"] = $nodeName[0];
            if (preg_match_all("/\[@(.*?)\]/", $pathItem, $match)) {
                foreach ($match[1] as $attr) {
                    list($attrName, $attrValue) = explode("=", $attr);
                    $result[$key]["attributes"][$attrName] = trim($attrValue, '"');
                }
            } else {
                $result[$key]["attributes"] = [];
            }
        }

        return $result;
    }

}
