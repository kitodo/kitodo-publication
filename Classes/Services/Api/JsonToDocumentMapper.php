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
use EWW\Dpf\Domain\Model\DocumentForm;
use EWW\Dpf\Domain\Model\DocumentFormField;
use EWW\Dpf\Domain\Model\DocumentFormGroup;
use EWW\Dpf\Domain\Model\DocumentType;
use EWW\Dpf\Domain\Model\MetadataGroup;
use EWW\Dpf\Domain\Model\MetadataObject;
use EWW\Dpf\Helper\DocumentMapper;
use EWW\Dpf\Helper\MetadataItemId;
use EWW\Dpf\Services\ProcessNumber\ProcessNumberGenerator;
use EWW\Dpf\Services\Xml\ParserGenerator;
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
     * Replaces the data from the document with the data from the json
     * @param Document $document
     * @param $jsonData
     * @return Document
     * @throws InvalidJson
     * @throws \JsonPath\InvalidJsonException
     */
    public function editDocument(Document $document, $jsonData)
    {
        $documentType = $this->getDocumentTypeFromJsonData($jsonData);
        $metaData = $this->getMetadataFromJson($jsonData, $document->getDocumentType());
        $this->checkMetadata($metaData);

        /** @var DocumentMapper $documentMapper */
        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        if ($documentType && $documentType->getUid() !== $document->getDocumentType()->getUid()) {
            $document->setDocumentType($documentType);
            $internalFormat = new \EWW\Dpf\Services\Api\InternalFormat($document->getXmlData());
            $internalFormat->setDocumentType($documentType->getName());
            $document->setXmlData($internalFormat->getXml());

            // Adjusting the document data according to the new document type
            $documentForm = $documentMapper->getDocumentForm($document);
        } else {
            $documentForm = $documentMapper->getDocumentForm($document);
        }

        $documentGroupsToRemove = [];

        foreach ($metaData as $group) {

            /** @var MetadataGroup $metaDataGroup */
            $metadataGroup = $this->metadataGroupRepository->findByUid($group['metadataGroup']);
            $jsonGroupName = $group['jsonGroupName'];

            foreach ($group['items'] as $groupItem) {

                $action = $groupItem['_action'];

                if (array_key_exists('_action', $groupItem) && in_array($action, ['remove', 'update', 'add'])) {
                    switch ($groupItem['_action']) {
                        case 'update':
                            $this->updateDocumentFormGroup($documentForm, $metadataGroup, $jsonGroupName, $groupItem);
                            break;

                        case 'add':
                            $this->addDocumentFormGroup($documentForm, $metadataGroup, $jsonGroupName, $groupItem);
                            break;

                        case 'remove':
                            if (array_key_exists('_index', $groupItem)) {
                                $groupIndex = (int)$groupItem['_index'];
                                $documentGroup = $this->findDocumentGroup($documentForm, $metadataGroup, $jsonGroupName, $groupIndex);
                                if ($documentGroup) {
                                    $documentGroupsToRemove[$groupIndex] = $documentGroup;
                                }
                            }
                            break;
                    }
                }
            }
        }

        if (krsort($documentGroupsToRemove)) {
            foreach ($documentGroupsToRemove as $documentGroup) {
                $documentForm->removeGroupItem($documentGroup);
            }
        }

        return $documentMapper->getDocument($documentForm);
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
        $documentType = $this->getDocumentTypeFromJsonData($jsonData);
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

               if (isset($groupItem['objects'])) {
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
               }

                $meta[] = $goupData;
            }
        }

        $exporter = new ParserGenerator();

        $documentData['documentUid'] = 0;
        $documentData['metadata'] = $meta;
        $documentData['files'] = array();

        $exporter->buildXmlFromForm($documentData);

        $internalXml = $exporter->getXMLData();
        $document->setXmlData($internalXml);

        $internalFormat = new InternalFormat($internalXml);

        $document->setTitle($internalFormat->getTitle());
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
            /** @var \EWW\Dpf\Domain\Model\DocumentType $documentType */
            $documentType = $this->getDocumentTypeFromJsonData($jsonData);
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
                                } else {
                                    if (
                                        $metadataObject->getInputField() === \EWW\Dpf\Domain\Model\MetadataObject::hidden
                                        && $metadataObject->getDefaultValue()
                                    ) {
                                        $objectMetaData = [];
                                        $objectMetaData['jsonObjectName'] = "__HIDDEN_UID" . $metadataObject->getUid() ;
                                        $objectMetaData['metadataObject'] = $metadataObject->getUid();
                                        $objectMetaData['items'] = [];
                                        $objectMetaData['items'][] = [
                                            "_value" => $metadataObject->getDefaultValue(),
                                            "_index" => 0,
                                            "_action" => "update"
                                        ];
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

    protected function addDocumentFormGroup(DocumentForm $documentForm, $metadataGroup, $jsonGroupName, $groupItem)
    {
        $lastGroupIndex = $this->findLastGroupIndex($documentForm, $metadataGroup, $jsonGroupName);

        if ($lastGroupIndex !== null) {
            $documentFormGroup = new DocumentFormGroup($metadataGroup);
            $newGroupIndex = $lastGroupIndex + 1;
            $groupMetadataItemId = new MetadataItemId($metadataGroup->getUid() . '-' . $newGroupIndex);
            $documentFormGroup->setId($groupMetadataItemId->__toString());

            if (isset($groupItem['objects']) && is_array($groupItem['objects'])) {

                foreach ($groupItem['objects'] as $object) {
                    $fieldIndex = 0;

                    if (isset($object['metadataObject'])) {
                        /** @var MetadataObject $metadataObject */
                        $metadataObject = $this->metadataObjectRepository->findByUid($object['metadataObject']);
                        $fieldMapping = trim($metadataObject->getMapping(), " /");

                        foreach ($object['items'] as $objectItem) {
                            if ($objectItem) {

                                $itemMetadataItemId = new MetadataItemId(
                                    $metadataGroup->getUid()
                                    . '-' . $newGroupIndex
                                    . '-' . $metadataObject->getUid()
                                    . '-' . $fieldIndex
                                );

                                $documentFormField = new DocumentFormField();
                                $documentFormField->setUid($metadataObject->getUid());
                                $documentFormField->setId($itemMetadataItemId->__toString());

                                if (isset($objectItem['_value'])) {
                                    $documentFormField->setValue($objectItem['_value']);
                                }

                                $documentFormGroup->addItem($documentFormField);

                                $fieldIndex++;
                            }

                            if (str_starts_with($fieldMapping, "@") || $fieldMapping === ".") {
                                break;
                            }
                        }
                    }
                }
            }

            $documentForm->addGroupItem($documentFormGroup);

        }

    }

    /**
     * @param DocumentForm $documentForm
     * @param $metadataGroup
     * @param $jsonGroupName
     * @return int|null
     * @throws \Exception
     */
    private function findLastGroupIndex(DocumentForm $documentForm, $metadataGroup, $jsonGroupName): ?int
    {

        $lastGroupIndex = -1;
        $jsonGroupMapping = trim($metadataGroup->getJsonMapping(), "$.* ");

        if (empty($jsonGroupMapping) || $jsonGroupName !== $jsonGroupMapping) {
            return null;
        }

        foreach ($documentForm->getItems() as $formPages) {
            foreach ($formPages as $formPageItem) {
                foreach ($formPageItem->getItems() as $formGroup) {
                    foreach ($formGroup as $formGroupItem) {
                        if (!$formGroupItem->getId()) {
                            throw new \Exception("Missing metadata-item-id");
                        }

                        $metadataItemId = new MetadataItemId($formGroupItem->getId());

                        $existingMetadataGroup = $this->metadataGroupRepository->findByUid($formGroupItem->getUid());
                        if ($existingMetadataGroup) {
                            $existingJsonMapping = trim($existingMetadataGroup->getJsonMapping(), "$.* ");

                            if ($existingJsonMapping === $jsonGroupName) {
                                $currentGroupIndex = $metadataItemId->getGroupIndex();
                                $lastGroupIndex = max($lastGroupIndex, $currentGroupIndex);
                            }
                        }
                    }
                }
            }
        }

        return $lastGroupIndex >= 0 ? $lastGroupIndex : null;
    }

    private function findDocumentGroup(DocumentForm $documentForm, $metadataGroup, $jsonGroupName, $index): ?DocumentFormGroup
    {
        foreach ($documentForm->getItems() as $formPages) {
            foreach ($formPages as $formPageItem) {
                foreach ($formPageItem->getItems() as $formGroup) {
                    $groupIndex = 0;
                    foreach ($formGroup as $formGroupItem) {
                        $existingMetadataGroup = $this->metadataGroupRepository->findByUid($formGroupItem->getUid());
                        if ($existingMetadataGroup) {
                            $existingJsonMapping = trim($existingMetadataGroup->getJsonMapping(), "$.* ");

                            if ($existingJsonMapping === $jsonGroupName && $groupIndex === $index) {
                                return $formGroupItem;
                            }
                            $groupIndex++;
                        }
                    }
                }
            }
        }

        return null;
    }

    private function findDocumentFormField(DocumentFormGroup $documentFormGroup, $metadataObject, $jsonFieldName, $index): ?DocumentFormField
    {
        foreach ($documentFormGroup->getItems() as $field) {
            $fieldIndex = 0;
            foreach ($field as $fieldItem) {
                $existingMetadataField = $this->metadataObjectRepository->findByUid($fieldItem->getUid());
                if ($existingMetadataField) {

                    $existingJsonMapping = trim($existingMetadataField->getJsonMapping(), "$.* ");

                    if ($existingJsonMapping === $jsonFieldName && $fieldIndex === $index) {
                        return $fieldItem;
                    }

                    $defaultFieldValue = trim($existingMetadataField->getDefaultValue());

                    if (
                        $existingMetadataField->getInputField() === \EWW\Dpf\Domain\Model\MetadataObject::hidden
                        && $defaultFieldValue
                        && $jsonFieldName == "__HIDDEN_UID" . $existingMetadataField->getUid()) {

                        return $fieldItem;
                    }

                    $fieldIndex++;
                }
            }
        }

        return null;
    }


    protected function updateDocumentFormGroup(DocumentForm $documentForm, $metadataGroup, $jsonGroupName, $groupItem)
    {
        if (!array_key_exists('_index', $groupItem) || !is_numeric($groupItem['_index'])) {
            throw new InvalidJson("Group $jsonGroupName, invalid or missing parameter _index for update action");
        }

        $groupIndex = (int)$groupItem['_index'];
        $documentFormGroup = $this->findDocumentGroup($documentForm, $metadataGroup, $jsonGroupName, $groupIndex);

        if (!$documentFormGroup) {
            throw new InvalidJson("Group $jsonGroupName with index $groupIndex not found for update");
        }

        if (!isset($groupItem['objects']) || !is_array($groupItem['objects'])) {
            return;
        }

        foreach ($groupItem['objects'] as $object) {
            if (!isset($object['metadataObject'])) {
                continue;
            }

            /** @var MetadataObject $metadataObject */
            $metadataObject = $this->metadataObjectRepository->findByUid($object['metadataObject']);
            if (!$metadataObject) {
                continue;
            }


            /** @var MetadataObject $metadataObject */
            $metadataObject = $this->metadataObjectRepository->findByUid($object['metadataObject']);
            $jsonFieldName = $object['jsonObjectName'];

            $fieldsToRemove = [];

            if (!isset($object['items']) || !is_array($object['items'])) {
                continue;
            }

            foreach ($object['items'] as $objectItem) {

                $action = $objectItem['_action'];

                if (!array_key_exists('_action', $objectItem) || !in_array($objectItem['_action'], ['remove', 'update', 'add'])) {
                    continue;
                }

                switch ($action) {
                    case 'update':
                        $this->updateDocumentFormField($documentFormGroup, $metadataObject, $jsonFieldName, $objectItem);
                        break;

                    case 'add':
                        $this->addDocumentFormField($documentFormGroup, $metadataObject, $jsonFieldName, $objectItem);
                        break;

                    case 'remove':
                        if (array_key_exists('_index', $objectItem) && is_numeric($objectItem['_index'])) {
                            $fieldIndex = (int)$objectItem['_index'];
                            $documentFormField = $this->findDocumentFormField($documentFormGroup, $metadataGroup, $jsonFieldName, $fieldIndex);
                            if ($documentFormField) {
                                $fieldsToRemove[$fieldIndex] = $documentFormField;
                            }
                        }
                        break;
                }
            }

            if (krsort($fieldsToRemove)) {
                foreach ($fieldsToRemove as $fieldToRemove) {
                    $documentFormGroup->removeItem($fieldToRemove);
                }
            }
        }
    }

    private function updateDocumentFormField(DocumentFormGroup $documentFormGroup, MetadataObject $metadataObject, string $jsonFieldName, array $objectItem)
    {
        if (!array_key_exists('_index', $objectItem) || !is_numeric($objectItem['_index'])) {
            throw new InvalidJson("Field $jsonFieldName update requires valid _index parameter");
        }

        if (!array_key_exists('_value', $objectItem)) {
            throw new InvalidJson("Field $jsonFieldName update requires _value parameter");
        }

        $fieldIndex = (int)$objectItem['_index'];
        $documentFormField = $this->findDocumentFormField($documentFormGroup, $metadataObject, $jsonFieldName, $fieldIndex);

        if ($documentFormField) {
            $documentFormField->setValue($objectItem['_value']);
        } else {
            throw new InvalidJson("Field $jsonFieldName with index $fieldIndex not found for update");
        }
    }

    private function addDocumentFormField(DocumentFormGroup $documentFormGroup, MetadataObject $metadataObject, string $jsonFieldName, array $objectItem)
    {
        if (!array_key_exists('_value', $objectItem)) {
            throw new InvalidJson("Field add requires _value parameter");
        }

        $lastFieldIndex = $this->findLastDocumentFormFieldIndex($documentFormGroup, $metadataObject, $jsonFieldName);

        if ($lastFieldIndex !== null) {
            $documentFormField = new DocumentFormField();
            $documentFormField->setUid($metadataObject->getUid());

            if ($this->isSingleField($metadataObject)) {
                $newFieldIndex = 0;
            } else {
                $newFieldIndex = $lastFieldIndex + 1;
            }

            $groupMetadataItemId = new MetadataItemId($documentFormGroup->getId());
            $fieldMetadataItemId = new MetadataItemId(
                $groupMetadataItemId->getGroupId()
                . '-' . $groupMetadataItemId->getGroupIndex()
                . '-' . $metadataObject->getUid()
                . '-' . $newFieldIndex
            );


            $documentFormField->setId($fieldMetadataItemId->__toString());
            $documentFormField->setValue($objectItem['_value']);
        }

        $documentFormGroup->addItem($documentFormField);
    }

    private function findLastDocumentFormFieldIndex(DocumentFormGroup $documentFormGroup, MetadataObject $metadataObject, $jsonFieldName): ?int
    {
        $lastFieldIndex = -1;
        $jsonFieldMapping = trim($metadataObject->getJsonMapping(), "$.* ");

        if (empty($jsonFieldMapping) || $jsonFieldName !== $jsonFieldMapping) {
            return null;
        }

        foreach ($documentFormGroup->getItems() as $field) {
            /** @var DocumentFormField $fieldItem */
            foreach ($field as $fieldItem) {
                if (!$fieldItem->getId()) {
                    throw new \Exception("Missing metadata-item-id");
                }

                $metadataItemId = new MetadataItemId($fieldItem->getId());
                $existingMetadataField = $this->metadataObjectRepository->findByUid($fieldItem->getUid());
                if ($existingMetadataField) {
                    $existingJsonMapping = trim($existingMetadataField->getJsonMapping(), "$.* ");

                    if ($existingJsonMapping === $jsonFieldName) {
                        $currentFieldIndex = $metadataItemId->getFieldIndex();
                        $lastFieldIndex = max($lastFieldIndex, $currentFieldIndex);
                    }
                }
            }
        }

        return $lastFieldIndex >= 0 ? $lastFieldIndex : null;
    }

    private function isSingleField(MetadataObject $metadataObject)
    {
        $metadataObjectMapping = $metadataObject->mappping = trim($metadataObject->getMapping(), " /");
        return str_starts_with($metadataObjectMapping, "@") || $metadataObjectMapping === ".";
    }

    /**
     * @param $jsonData
     * @return DocumentType|null
     */
    public function getDocumentTypeFromJsonData($jsonData)
    {
        $jsonObject = new JsonObject($jsonData);
        $publicationType = $jsonObject->get('$.publicationType');

        if ($publicationType && is_array($publicationType)) {
            $publicationType = $publicationType[0];
        }

        return $this->documentTypeRepository->findOneByName($publicationType);
    }

}
