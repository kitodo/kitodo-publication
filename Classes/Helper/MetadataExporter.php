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

class MetadataExporter
{
    /**
     * metadataGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
     * @inject
     */
    protected $metadataGroupRepository = null;


    /**
     * metadataObjectRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataObjectRepository
     * @inject
     */
    protected $metadataObjectRepository = null;


    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return string
     */
    public function getMetsXml($document)
    {
        $exporter = new \EWW\Dpf\Services\MetsExporter();

        $fileData = $document->getFileData();

        $exporter->setFileData($fileData);

        $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());

        $dateIssued = $document->getDateIssued();

        $mods->setDateIssued($dateIssued);

        $exporter->setMods($mods->getModsXml());

        $exporter->setSlubInfo($document->getSlubInfoData());

        $exporter->setObjId($document->getObjectIdentifier());

        $exporter->buildMets();

        return $exporter->getMetsData();
    }


    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return string
     */
    public function getModsXml($document)
    {
        $metadataForExporter = $this->getMetadataForExporter($document);

        $exporter = new \EWW\Dpf\Services\MetsExporter();

        // mods:mods
        $modsData['documentUid'] = $document->getUid();
        $modsData['metadata'] = $metadataForExporter['mods'];
        $modsData['files'] = array();

        $exporter->buildModsFromForm($modsData);
        return $exporter->getModsData();
    }


    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return string
     */
    public function getSlubInfoXml($document)
    {
        $metadataForExporter = $this->getMetadataForExporter($document);

        $exporter = new \EWW\Dpf\Services\MetsExporter();

        // slub:info
        $slubInfoData['documentUid'] = $document->getUid();
        $slubInfoData['metadata'] = $metadataForExporter['slubInfo'];
        $slubInfoData['files'] = array();

        $exporter->buildSlubInfoFromForm($slubInfoData, $document->getDocumentType(), $document->getProcessNumber());
        return $exporter->getSlubInfoData();
    }


    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return mixed
     */
    protected function getMetadataForExporter(\EWW\Dpf\Domain\Model\Document $document)
    {
        foreach ($document->getMetadata() as $groupUid => $group) {

            foreach ($group as $groupIndex => $groupItem) {

                $item = array();

                $metadataGroup = $this->metadataGroupRepository->findByUid($groupUid);

                $item['mapping'] = $metadataGroup->getRelativeMapping();

                $item['modsExtensionMapping'] = $metadataGroup->getRelativeModsExtensionMapping();

                $item['modsExtensionReference'] = trim($metadataGroup->getModsExtensionReference(), " /");

                $item['groupUid'] = $groupUid;

                $fieldValueCount = 0;
                $defaultValueCount = 0;
                $fieldCount = 0;
                foreach ($groupItem as $fieldUid => $field) {
                    foreach ($field as $fieldIndex => $fieldItem) {
                        $metadataObject = $this->metadataObjectRepository->findByUid($fieldUid);

                        $fieldMapping = $metadataObject->getRelativeMapping();

                        $formField = array();

                        $value = $fieldItem;

                        if ($metadataObject->getDataType() == \EWW\Dpf\Domain\Model\MetadataObject::INPUT_DATA_TYPE_DATE) {
                            $date = date_create_from_format('d.m.Y', trim($value));
                            if ($date) {
                                $value = date_format($date, 'Y-m-d');
                            }
                        }

                        $fieldCount++;
                        if (!empty($value)) {
                            $fieldValueCount++;
                            $defaultValue = $metadataObject->getDefaultValue();
                            if ($defaultValue) {
                                $defaultValueCount++;
                            }
                        }

                        $value = str_replace('"', "'", $value);
                        if ($value) {
                            $formField['modsExtension'] = $metadataObject->getModsExtension();

                            $formField['mapping'] = $fieldMapping;
                            $formField['value'] = $value;

                            if (strpos($fieldMapping, "@") === 0) {
                                $item['attributes'][] = $formField;
                            } else {
                                $item['values'][] = $formField;
                            }
                        }
                    }
                }

                if (!key_exists('attributes', $item)) {
                    $item['attributes'] = array();
                }

                if (!key_exists('values', $item)) {
                    $item['values'] = array();
                }

                if ($metadataGroup->getMandatory() || $defaultValueCount < $fieldValueCount || $defaultValueCount == $fieldCount) {
                    if ($metadataGroup->isSlubInfo($metadataGroup->getMapping())) {
                        $form['slubInfo'][] = $item;
                    } else {
                        $form['mods'][] = $item;
                    }
                }

            }

        }

        return $form;
    }
}
