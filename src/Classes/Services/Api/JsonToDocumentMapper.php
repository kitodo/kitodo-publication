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

        $exporter = new \EWW\Dpf\Services\MetsExporter();

        // mods:mods
        $modsData['documentUid'] = 0;
        $modsData['metadata']    = $metaData['mods'];
        $modsData['files']       = array();

        $exporter->buildModsFromForm($modsData);
        $modsXml = $exporter->getModsData();
        $document->setXmlData($modsXml);

        $mods = new \EWW\Dpf\Helper\Mods($modsXml);

        $document->setTitle($mods->getTitle());
        $document->setAuthors($mods->getAuthors());
        $document->setDateIssued($mods->getDateIssued());
        //$document->setEmbargoDate($formMetaData['embargo']);

        // slub:info
        $slubInfoData['documentUid'] = 0;
        $slubInfoData['metadata']    = $metaData['slubInfo'];
        $slubInfoData['files']       = array();
        $exporter->buildSlubInfoFromForm($slubInfoData, $documentType, $document->getProcessNumber());
        $slubInfoXml = $exporter->getSlubInfoData();

        $document->setSlubInfoData($slubInfoXml);

        $document->setState(\EWW\Dpf\Domain\Workflow\DocumentWorkflow::STATE_REGISTERED_NONE);

        return $document;
    }


    public function getMetadataFromJson($jsonData)
    {
        $jsonObject = new JsonObject($jsonData);
        $publicationType = $jsonObject->get('$.publicationType');
        if ($publicationType && is_array($publicationType)) {
            $publicationType = $publicationType[0];
        }

        $resultData = [];

        /** @var \EWW\Dpf\Domain\Model\DocumentType $documentType */
        $documentType = $this->documentTypeRepository->findOneByName($publicationType);
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

                            if ($value) {
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
                            }
                        }
                    }

                    // ToDo: Does this need a further if condition like in self::getMetadata()
                    if ($metadataGroup->isSlubInfo($metadataGroup->getMapping())) {
                        $resultData['slubInfo'][] = $resultGroup;;
                    } else {
                        $resultData['mods'][] = $resultGroup;;
                    }
                }

            }
        }

        return $resultData;
    }

}
