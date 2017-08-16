<?php
namespace EWW\Dpf\Domain\Factory;

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

use \EWW\Dpf\Domain\Model\Document;

class DocumentFactory {

    static function createByMets($remoteId, $metsXml)
    {

        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\Object\\ObjectManager');
        $documentTypeRepository = $objectManager->get("EWW\\Dpf\\Domain\\Repository\\DocumentTypeRepository");

        $mets = new \EWW\Dpf\Helper\Mets($metsXml);
        $mods = $mets->getMods();
        $slub = $mets->getSlub();

        $title   = $mods->getTitle();
        $authors = $mods->getAuthors();

        $documentTypeName = $slub->getDocumentType();
        $documentType     = $documentTypeRepository->findOneByName($documentTypeName);

        if (empty($title) || empty($documentType)) {
            return false;
        }

        $state = $mets->getState();

        switch ($state) {
            case "ACTIVE":
                $objectState = Document::OBJECT_STATE_ACTIVE;
                break;
            case "INACTIVE":
                $objectState = Document::OBJECT_STATE_INACTIVE;
                break;
            case "DELETED":
                $objectState = Document::OBJECT_STATE_DELETED;
                break;
            default:
                $objectState = "ERROR";
                throw new \Exception("Unknown object state: " . $state);
                break;
        }

        $document = $objectManager->get('\EWW\Dpf\Domain\Model\Document');
        $document->setObjectIdentifier($remoteId);
        $document->setState($objectState);
        $document->setTitle($title);
        $document->setAuthors($authors);
        $document->setDocumentType($documentType);

        $document->setXmlData($mods->getModsXml());
        $document->setSlubInfoData($slub->getSlubXml());

        $document->setDateIssued($mods->getDateIssued());

        $document->setProcessNumber($slub->getProcessNumber());

        foreach ($mets->getFiles() as $attachment) {

            $file = $objectManager->get('\EWW\Dpf\Domain\Model\File');
            $file->setContentType($attachment['mimetype']);
            $file->setDatastreamIdentifier($attachment['id']);
            $file->setLink($attachment['href']);
            $file->setTitle($attachment['title']);
            $file->setLabel($attachment['title']);
            $file->setDownload($attachment['download']);
            $file->setArchive($attachment['archive']);

            if ($attachment['id'] == \EWW\Dpf\Domain\Model\File::PRIMARY_DATASTREAM_IDENTIFIER) {
                $file->setPrimaryFile(true);
            }

            $document->addFile($file);
        }

       $document->setMetadata(self::createMetadata($document, $metsXml));

        //echo "<pre>";
        //print_r($document->getMetadata());
        //echo "</pre>";
        //die();

        return $document;
    }


    private static function createMetadata($document, $metsXml) {

        $mets = new \EWW\Dpf\Helper\Mets($metsXml);
        $mods = $mets->getMods();
        $slub = $mets->getSlub();

        $documentGroupData = array();

        foreach ($document->getDocumentType()->getMetadataPage() as $metadataPage) {

            foreach ($metadataPage->getMetadataGroup() as $metadataGroup) {

                if ($metadataGroup->isSlubInfo($metadataGroup->getMapping())) {
                    $xpath = $slub->getSlubXpath();
                } else {
                    $xpath = $mods->getModsXpath();
                }

                // get fixed attributes from xpath configuration
                $fixedGroupAttributes = array();

                preg_match_all('/[A-Za-z0-9:@\.]+(\[@.*?\])*/', $metadataGroup->getAbsoluteMapping(),
                    $groupMappingPathParts);
                $groupMappingPathParts = $groupMappingPathParts[0];

                $groupMappingPath = end($groupMappingPathParts);
                $groupMappingName = preg_replace('/\[@.+?\]/', '', $groupMappingPath);

                if (preg_match_all('/\[@.+?\]/', $groupMappingPath, $matches)) {
                    $fixedGroupAttributes = $matches[0];
                }

                // build mapping path, previous fixed attributes which are differ from
                // the own fixed attributes are excluded
                $queryGroupMapping = $metadataGroup->getAbsoluteMapping();
                if (strpos($queryGroupMapping,
                        "@displayLabel") === false && is_array($excludeGroupAttributes[$groupMappingName])
                ) {
                    foreach ($excludeGroupAttributes[$groupMappingName] as $excludeAttr => $excludeAttrValue) {
                        if (!in_array($excludeAttr, $fixedGroupAttributes)) {
                            $queryGroupMapping .= $excludeAttrValue;
                        }
                    }
                }

                // Read the group data.
                if ($metadataGroup->hasMappingForReading()) {
                    $groupData = $xpath->query($metadataGroup->getAbsoluteMappingForReading());
                } else {
                    $groupData = $xpath->query($queryGroupMapping);
                }

                // Fixed attributes from groups must be excluded in following xpath queries
                foreach ($fixedGroupAttributes as $excludeGroupAttribute) {
                    $excludeGroupAttributes[$groupMappingName][$excludeGroupAttribute] = "[not(" . trim($excludeGroupAttribute,
                            "[] ") . ")]";
                }

                if ($groupData->length > 0) {
                    foreach ($groupData as $key => $data) {

                        $documentFieldData = array();

                        foreach ($metadataGroup->getMetadataObject() as $metadataObject) {

                            $objectMapping = "";

                            preg_match_all('/([A-Za-z0-9]+:[A-Za-z0-9]+(\[.*\])*|[A-Za-z0-9:@\.]+)/',
                                $metadataObject->getRelativeMapping(), $objectMappingPath);
                            $objectMappingPath = $objectMappingPath[0];

                            foreach ($objectMappingPath as $key => $value) {

                                // ensure that e.g. <mods:detail> and <mods:detail type="volume">
                                // are not recognized as the same node
                                if ((strpos($value, "@") === false) && ($value != '.')) {
                                    $objectMappingPath[$key] .= "[not(@*)]";
                                }
                            }

                            $objectMapping = implode("/", $objectMappingPath);

                            if ($objectMapping == '[not(@*)]' || empty($objectMappingPath)) {
                                $objectMapping = '.';
                            }

                            if ($metadataObject->isModsExtension()) {

                                $referenceAttribute = $metadataGroup->getModsExtensionReference();
                                $modsExtensionGroupMapping = $metadataGroup->getAbsoluteModsExtensionMapping();

                                $refID = $data->getAttribute("ID");
                                // filter hashes from referenceAttribute value for backwards compatibility reasons
                                $objectData = $xpath->query($modsExtensionGroupMapping . "[translate(@" . $referenceAttribute . ",'#','')=" . '"' . $refID . '"]/' . $objectMapping);
                            } else {
                                $objectData = $xpath->query($objectMapping, $data);
                            }

                            if ($objectData->length > 0) {

                                foreach ($objectData as $key => $value) {

                                   // $documentFormFieldItem = clone ($documentFormField);

                                    $objectValue = $value->nodeValue;

                                    if ($metadataObject->getDataType() == \EWW\Dpf\Domain\Model\MetadataObject::INPUT_DATA_TYPE_DATE) {
                                        $dateStr = explode('T', $objectValue);
                                        $date = date_create_from_format('Y-m-d', trim($dateStr[0]));
                                        if ($date) {
                                            $objectValue = date_format($date, 'd.m.Y');
                                        }
                                    }

                                    $objectValue = str_replace('"', "'", $objectValue);

                                    //$fieldItem = setValue($objectValue, $metadataObject->getDefaultValue());
                                    $documentFieldData[$metadataObject->getUid()][] = $objectValue;
                                }
                            } else {
                                //$documentFormField->setValue("", $metadataObject->getDefaultValue());
                                $documentFieldData[$metadataObject->getUid()][] = "";
                            }

                        }

                        $documentGroupData[$metadataGroup->getUid()][] = $documentFieldData;
                    }
                } else {
                    $documentFieldData = array();
                    foreach ($metadataGroup->getMetadataObject() as $metadataObject) {
                        //$documentFormField->setValue("", $metadataObject->getDefaultValue());
                        $documentFieldData[$metadataObject->getUid()][] = "";
                    }
                    $documentGroupData[$metadataGroup->getUid()][] = $documentFieldData;
                }
            }

        }

        return $documentGroupData;
    }

}