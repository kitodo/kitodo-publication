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
use EWW\Dpf\Domain\Model\DocumentType;
use EWW\Dpf\Domain\Model\MetadataGroup;
use EWW\Dpf\Domain\Model\MetadataObject;
use EWW\Dpf\Helper\DocumentMapper;
use EWW\Dpf\Services\ParserGenerator;
use EWW\Dpf\Services\ProcessNumber\ProcessNumberGenerator;
use JsonPath\JsonObject;

class JsonToDocumentMapper
{
    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $objectManager;

    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentTypeRepository = null;

    /**
     * MetadataGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
     * @inject
     */
    protected $metadataGroupRepository = null;

    /**
     * MetadataObjectRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataObjectRepository
     * @inject
     */
    protected $metadataObjectRepository = null;

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentRepository = null;

    /**
     * @var InternalXml
     */
    protected $internalXml = null;

    /**
     * Replaces the data from the document with the data from the json
     * @param Document $document
     * @param $jsonData
     * @return Document
     * @throws InvalidJson
     * @throws \JsonPath\InvalidJsonException
     */
    public function editDocument(Document $document, $jsonData)
    {
        $this->internalXml = new InternalXml();
        $xmlData = $document->getXmlData();
        $this->internalXml->setXml($xmlData);

        $metaData = $this->getMetadataFromJson($jsonData, $document->getDocumentType());

        $this->checkMetadata($metaData);

        foreach (['update', 'add', 'remove'] as $action) {
            foreach ($metaData as $group) {
                $metaDataGroup = $this->metadataGroupRepository->findByUid($group['metadataGroup']);
                foreach ($group['items'] as $groupItem) {
                    if (array_key_exists('_action', $groupItem) && $groupItem['_action'] === $action) {
                        switch ($groupItem['_action']) {
                            case 'update':
                                $this->updateGroup($metaDataGroup, $groupItem);
                                break;

                            case 'add':
                                $this->addGroup($metaDataGroup, $groupItem);
                                break;

                            case 'remove':
                                $this->removeGroup($metaDataGroup, $groupItem);
                                break;
                        }
                    }
                }
            }
        }

        $document->setXmlData($this->internalXml->getXml());

        /** @var DocumentMapper $documentMapper */
        $documentMapper = $this->objectManager->get(DocumentMapper::class);
        // Fixme: Due to some php xml/xpath limitations the document xml needs to be ordered,
        // so that the same groups stand one behind the other in the xml.
        $documentForm = $documentMapper->getDocumentForm($document);
        $document = $documentMapper->getDocument($documentForm);

        return $document;
    }

    /**
     * Creates a document from the given json data
     *
     * @param string $jsonData
     * @return Document $document
     * @throws InvalidJson
     * @throws \JsonPath\InvalidJsonException
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

        $metaData = $this->getMetadataFromJson($jsonData);
        $this->checkMetadata($metaData, false, false);

        /** @var Document $document */
        $document = $this->objectManager->get(Document::class);

        $document->setDocumentType($documentType);

        $processNumberGenerator = $this->objectManager->get(ProcessNumberGenerator::class);
        $processNumber = $processNumberGenerator->getProcessNumber();
        $document->setProcessNumber($processNumber);

        $meta = [];

        foreach ($metaData as $group) {
            $metadataGroup = $this->metadataGroupRepository->findByUid($group['metadataGroup']);

            foreach ($group['items'] as $groupItem) {
               $goupData = [
                    'groupUid' => $metadataGroup->getUid(),
                    'mapping' => $metadataGroup->getRelativeMapping(),
                    'modsExtensionMapping' => $metadataGroup->getRelativeModsExtensionMapping(),
                    'modsExtensionReference' => trim(
                        $metadataGroup->getModsExtensionReference(),
                        " /"
                    ),
                   'values' => [],
                   'attributes' => []
                ];

                foreach ($groupItem['objects'] as $object) {
                    $metadataObject = $this->metadataObjectRepository->findByUid($object['metadataObject']);
                    $fieldMapping = $metadataObject->getRelativeMapping();
                    foreach ($object['items'] as $objectItemKey => $objectItem) {
                        $objectData = [
                            'modsExtension' => $metadataObject->getModsExtension(),
                            'mapping' => $fieldMapping,
                            'value' => $objectItem['_value']
                        ];

                        if (strpos($fieldMapping, "@") === 0) {
                            $goupData['attributes'][] = $objectData;
                        } else {
                            $goupData['values'][] = $objectData;
                        }
                    }
                }

                $meta[] = $goupData;
            }
        }

        $exporter = new \EWW\Dpf\Services\ParserGenerator();

        $documentData['documentUid'] = 0;
        $documentData['metadata'] = $meta;
        $documentData['files'] = array();

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

        /** @var DocumentMapper $documentMapper */
        $documentMapper = $this->objectManager->get(DocumentMapper::class);
        // Fixme: Due to some php xml/xpath limitations the document xml needs to be ordered,
        // so that the same groups stand one behind the other in the xml.
        // Since the JsonToDocumentMapper does not handle the metadata-item-id for groups and fields
        // this also ensures we have metadata-item-ids in the resulting xml data.
        $documentForm = $documentMapper->getDocumentForm($document);
        $document = $documentMapper->getDocument($documentForm);

        $document->setState(\EWW\Dpf\Domain\Workflow\DocumentWorkflow::STATE_REGISTERED_NONE);

        return $document;
    }

    /**
     * @param array $jsonData
     * @param DocumentType $documentType
     * @return array
     * @throws \JsonPath\InvalidJsonException
     */
    public function getMetadataFromJson($jsonData, DocumentType $documentType = null)
    {
        $jsonData = empty($jsonData) ? null : $jsonData;

        // Normalizing JSON data
        $arrayFromJson = json_decode($jsonData, true);
        foreach ($arrayFromJson as $groupKey => $groupData) {
            if (is_array($groupData) && array_key_first($groupData) !== 0) {
                $arrayFromJson[$groupKey] = [$groupData];
            }
        }
        foreach ($arrayFromJson as $groupKey => $groupData) {
            foreach ($arrayFromJson[$groupKey] as $groupIndex => $groupItem) {
                foreach ($groupItem as $objectKey => $objectData) {
                    if (substr($objectKey, 0, 1) !== '_') {
                        if (is_array($objectData) && array_key_first($objectData) !== 0) {
                            $arrayFromJson[$groupKey][$groupIndex][$objectKey] = [$objectData];
                        }
                    }
                }
            }
        }
        $jsonData = json_encode($arrayFromJson);
        // End of normalizing JSON data

        $jsonObject = new JsonObject($jsonData);

        if (empty($documentType)) {
            $publicationType = $jsonObject->get('$.publicationType');
            if ($publicationType && is_array($publicationType)) {
                $publicationType = $publicationType[0];
            }
            /** @var \EWW\Dpf\Domain\Model\DocumentType $documentType */
            $documentType = $this->documentTypeRepository->findOneByName($publicationType);
        }

        $metaData = [];

        foreach ($documentType->getMetadataPage() as $metadataPage) {

            /** @var MetadataGroup $metadataGroup */
            foreach ($metadataPage->getMetadataGroup() as $metadataGroup) {

                $jsonGroupMapping = trim($metadataGroup->getJsonMapping(), "$.*");

                if (!empty($jsonGroupMapping)) {

                    $jsonGroupItems = $jsonObject->get("$." . $jsonGroupMapping . ".*");

                    if (is_array($jsonGroupItems) && sizeof($jsonGroupItems) > 0) {

                        $groupMetaData = [];
                        $groupMetaData['jsonGroupName'] = $jsonGroupMapping;
                        $groupMetaData['metadataGroup'] = $metadataGroup->getUid();
                        $groupMetaData['items'] = [];

                        $groupMetaDataObjects = [];

                        foreach ($jsonGroupItems as $jsonGroupItem) {

                            $tempJson = json_encode($jsonGroupItem);
                            $tempJsonObject = new JsonObject($tempJson);
                            $groupMetaDataObjects['_index'] = $tempJsonObject->get("$._index")[0];
                            $groupMetaDataObjects['_action'] = $tempJsonObject->get("$._action")[0];

                            /** @var MetadataObject $metadataObject */
                            foreach ($metadataGroup->getMetadataObject() as $metadataObject) {
                                $jsonObjectMapping = trim($metadataObject->getJsonMapping(), "$.*");
                                if (!empty($jsonObjectMapping)) {
                                    $jsonObjectItems = $tempJsonObject->get("$." . $jsonObjectMapping . ".*");

                                    if (is_array($jsonObjectItems) && sizeof($jsonObjectItems) > 0) {
                                        $objectMetaData = [];
                                        $objectMetaData['jsonObjectName'] = $jsonObjectMapping;
                                        $objectMetaData['metadataObject'] = $metadataObject->getUid();
                                        $objectMetaData['items'] = [];
                                        foreach ($jsonObjectItems as $jsonObjectItem) {
                                            $objectMetaData['items'][] = $jsonObjectItem;
                                        }

                                        $groupMetaDataObjects['objects'][] = $objectMetaData;
                                    }
                                }
                            }
                            $groupMetaData['items'][] = $groupMetaDataObjects;
                            $groupMetaDataObjects = [];
                        }

                        $metaData[] = $groupMetaData;
                    }
                }
            }
        }

        return $metaData;
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

    /**
     * @param $metaDataGroup
     * @param $groupItem
     */
    protected function updateGroup($metaDataGroup, $groupItem)
    {
        $groupIndex = 0;
        if (array_key_exists('_index', $groupItem)) {
            $groupIndex = $groupItem['_index'];
        }

        /** @var GroupNode $groupNode */
        $groupNode = $this->internalXml->findGroup($metaDataGroup, $groupIndex);

        if ($groupNode instanceof GroupNode) {
            if (is_array($groupItem['objects'])) {

                foreach (['update', 'add', 'remove'] as $action) {

                    foreach ($groupItem['objects'] as $object) {
                    $metadataObject = $this->metadataObjectRepository->findByUid($object['metadataObject']);

                        foreach ($object['items'] as $objectItem) {

                            $objectIndex = 0;
                            if (array_key_exists('_index', $objectItem)) {
                                $objectIndex = intval($objectItem['_index']);
                            }

                            if (array_key_exists('_action', $objectItem) && $objectItem['_action'] === $action) {
                                switch ($objectItem['_action']) {
                                    case 'add':
                                        $this->internalXml->addField($groupNode, $metadataObject, $objectItem['_value']);
                                        break;

                                    case 'update':
                                        $this->internalXml->setField(
                                            $groupNode, $metadataObject, $objectIndex,
                                            $objectItem['_value']
                                        );
                                        break;

                                    case 'remove':
                                        $this->internalXml->removeField($groupNode, $metadataObject, $objectIndex);
                                        break;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $metaDataGroup
     * @param $groupItem
     */
    protected function addGroup($metaDataGroup, $groupItem)
    {
        $fieldData = [];
        foreach ($groupItem['objects'] as $object) {
            $metadataObject = $this->metadataObjectRepository->findByUid($object['metadataObject']);
            foreach ($object['items'] as $objectItem) {
                $fieldData[] = [
                    'metadataObject' => $metadataObject,
                    'value' => $objectItem['_value']
                ];
            }
        }

        /** @var GroupNode $groupNode */
        $this->internalXml->addGroup($metaDataGroup, $fieldData);
    }

    /**
     * @param $metaDataGroup
     * @param $groupItem
     */
    protected function removeGroup($metaDataGroup, $groupItem)
    {
        $groupIndex = 0;
        if (array_key_exists('_index', $groupItem)) {
            $groupIndex = $groupItem['_index'];
        }

        $this->internalXml->removeGroup($metaDataGroup, $groupIndex);
    }

    /**
     * @param $metaData
     * @param bool $checkIndex
     * @param bool $checkAction
     * @throws InvalidJson
     */
    protected function checkMetadata($metaData, $checkIndex = true, $checkAction = true) {

      foreach ($metaData as $data) {

          $jsonGroupName = $data['jsonGroupName'];

          if (is_array($data['items'])) {
              foreach ($data['items'] as $groupItem) {

                  if (
                      $checkIndex && (
                        !array_key_exists('_index', $groupItem)
                        || is_null($groupItem['_index'])
                        || !is_numeric($groupItem['_index'])
                      )
                  ) {
                      throw new InvalidJson("Group $jsonGroupName, invalid or missing parameter _index");
                  }
                  if (
                      $checkAction && (
                        !array_key_exists('_action', $groupItem)
                        || is_null($groupItem['_action'])
                        || !in_array($groupItem['_action'], ['add','remove','update'])
                      )
                  ) {
                      throw new InvalidJson("Group $jsonGroupName, invalid or missing parameter _action");
                  }

                  if (is_array($groupItem['objects'])) {
                      foreach ($groupItem['objects'] as $object) {
                          $jsonObjectName = $object['jsonObjectName'];
                          if (is_array($object['items'])) {
                              foreach ($object['items'] as $objectItem) {
                                  if (
                                      $checkIndex && (
                                          !array_key_exists('_index', $objectItem)
                                          || is_null($objectItem['_index'])
                                          || !is_numeric($objectItem['_index'])
                                      )
                                  ) {
                                      throw new InvalidJson(
                                          "Field $jsonObjectName, invalid or missing parameter _index"
                                      );
                                  }
                                  if (
                                      $checkAction && (
                                        !array_key_exists('_action', $objectItem)
                                        || is_null($objectItem['_action'])
                                        || !in_array($objectItem['_action'], ['add', 'remove', 'update'])
                                      )
                                  ) {
                                      throw new InvalidJson(
                                          "Field $jsonObjectName, invalid or missing parameter _action"
                                      );
                                  }
                                  if (
                                      !array_key_exists('_value', $objectItem)
                                      || is_null($objectItem['_value'])
                                  ) {
                                      throw new InvalidJson("Field $jsonObjectName, invalid or missing parameter _value");
                                  }
                              }
                          }
                      }
                  }
              }
          }
      }

    }

}
