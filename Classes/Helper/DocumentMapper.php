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

use EWW\Dpf\Services\Identifier\Urn;
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Services\ProcessNumber\ProcessNumberGenerator;

class DocumentMapper
{

    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $objectManager;

    /**
     *
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $configurationManager;

    /**
     * metadataGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $metadataGroupRepository = null;

    /**
     * metadataObjectRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataObjectRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $metadataObjectRepository = null;

    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentTypeRepository = null;

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentRepository = null;

    /**
     * fileRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FileRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $fileRepository = null;

    /**
     * depositLicenseRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DepositLicenseRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $depositLicenseRepository = null;

    /**
     * Get typoscript settings
     *
     * @return mixed
     */
    public function getSettings()
    {
        $frameworkConfiguration = $this->configurationManager->getConfiguration(
            \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );
        return $frameworkConfiguration['settings'];
    }

    /**
     * Gets the document form representation of the document data
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param bool $generateEmptyFields
     * @return \EWW\Dpf\Domain\Model\DocumentForm
     */
    public function getDocumentForm(Document $document, $generateEmptyFields = true)
    {
        $documentForm = new \EWW\Dpf\Domain\Model\DocumentForm();
        $documentForm->setUid($document->getDocumentType()->getUid());
        $documentForm->setDisplayName($document->getDocumentType()->getDisplayName());
        $documentForm->setName($document->getDocumentType()->getName());
        $documentForm->setDocumentUid($document->getUid());

        $documentForm->setPrimaryFileMandatory(
            (
                (
                    $this->getSettings()['deactivatePrimaryFileMandatoryCheck'] ||
                    $document->getDocumentType()->getVirtualType()
                )? false : true
            )
        );

        $documentForm->setProcessNumber($document->getProcessNumber());
        $documentForm->setTemporary($document->isTemporary());

        $qucosaId = $document->getObjectIdentifier();

        if (empty($qucosaId)) {
            $qucosaId = $document->getReservedObjectIdentifier();
        }

        $documentForm->setQucosaId($qucosaId);

        $internalFormat = new \EWW\Dpf\Helper\InternalFormat($document->getXmlData());

        $excludeGroupAttributes = array();

        foreach ($document->getDocumentType()->getMetadataPage() as $metadataPage) {
            $documentFormPage = new \EWW\Dpf\Domain\Model\DocumentFormPage();
            $documentFormPage->setUid($metadataPage->getUid());
            $documentFormPage->setDisplayName($metadataPage->getDisplayName());
            $documentFormPage->setName($metadataPage->getName());

            $documentFormPage->setAccessRestrictionRoles($metadataPage->getAccessRestrictionRoles());

            foreach ($metadataPage->getMetadataGroup() as $metadataGroup) {

                $documentFormGroup = new \EWW\Dpf\Domain\Model\DocumentFormGroup();
                $documentFormGroup->setUid($metadataGroup->getUid());
                $documentFormGroup->setDisplayName($metadataGroup->getDisplayName());
                $documentFormGroup->setName($metadataGroup->getName());
                $documentFormGroup->setMandatory($metadataGroup->getMandatory());

                $documentFormGroup->setAccessRestrictionRoles($metadataGroup->getAccessRestrictionRoles());

                $documentFormGroup->setInfoText($metadataGroup->getInfoText());
                $documentFormGroup->setGroupType($metadataGroup->getGroupType());
                $documentFormGroup->setMaxIteration($metadataGroup->getMaxIteration());

                $xpath = $internalFormat->getXpath();

                // get fixed attributes from xpath configuration
                $fixedGroupAttributes = array();

                preg_match_all('/[A-Za-z0-9:@\.]+(\[@.*?\])*/', $metadataGroup->getAbsoluteMapping(), $groupMappingPathParts);
                $groupMappingPathParts = $groupMappingPathParts[0];

                $groupMappingPath = end($groupMappingPathParts);
                $groupMappingName = preg_replace('/\[@.+?\]/', '', $groupMappingPath);

                if (preg_match_all('/\[@.+?\]/', $groupMappingPath, $matches)) {
                    $fixedGroupAttributes = $matches[0];
                }

                // build mapping path, previous fixed attributes which are differ from
                // the own fixed attributes are excluded
                $queryGroupMapping = $metadataGroup->getAbsoluteMapping();
                if (strpos($queryGroupMapping, "@displayLabel") === false && is_array($excludeGroupAttributes[$groupMappingName])) {
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
                    $excludeGroupAttributes[$groupMappingName][$excludeGroupAttribute] = "[not(" . trim($excludeGroupAttribute, "[] ") . ")]";
                }

                if ($groupData->length > 0) {
                    foreach ($groupData as $key => $data) {

                        $documentFormGroupItem = clone ($documentFormGroup);

                        foreach ($metadataGroup->getMetadataObject() as $metadataObject) {

                            $documentFormField = new \EWW\Dpf\Domain\Model\DocumentFormField();
                            $documentFormField->setUid($metadataObject->getUid());
                            $documentFormField->setDisplayName($metadataObject->getDisplayName());
                            $documentFormField->setName($metadataObject->getName());
                            $documentFormField->setMandatory($metadataObject->getMandatory());

                            $documentFormField->setAccessRestrictionRoles($metadataObject->getAccessRestrictionRoles());

                            $documentFormField->setConsent($metadataObject->getConsent());
                            $documentFormField->setValidation($metadataObject->getValidation());
                            $documentFormField->setDataType($metadataObject->getDataType());
                            $documentFormField->setMaxIteration($metadataObject->getMaxIteration());
                            $documentFormField->setInputField($metadataObject->getInputField());
                            $documentFormField->setInputOptions($metadataObject->getInputOptionList());
                            $documentFormField->setFillOutService($metadataObject->getFillOutService());
                            $documentFormField->setGndFieldUid($metadataObject->getGndFieldUid());
                            $documentFormField->setMaxInputLength($metadataObject->getMaxInputLength());
                            $documentFormField->setObjectType($metadataObject->getObjectType());

                            $depositLicense = $this->depositLicenseRepository->findByUid($metadataObject->getDepositLicense());
                            $documentFormField->setDepositLicense($depositLicense);

                            $objectMapping = "";

                            preg_match_all('/([A-Za-z0-9]+:[A-Za-z0-9]+(\[.*\])*|[A-Za-z0-9:@\.]+)/', $metadataObject->getRelativeMapping(), $objectMappingPath);
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

                                $referenceAttribute        = $metadataGroup->getModsExtensionReference();
                                $modsExtensionGroupMapping = $metadataGroup->getAbsoluteModsExtensionMapping();

                                $refID      = $data->getAttribute("ID");
                                // filter hashes from referenceAttribute value for backwards compatibility reasons
                                $objectData = $xpath->query($modsExtensionGroupMapping . "[translate(@" . $referenceAttribute . ",'#','')=" . '"' . $refID . '"]/' . $objectMapping);
                            } else {
                                $objectData = $xpath->query($objectMapping, $data);
                            }

                            $documentFormField->setValue("", $metadataObject->getDefaultValue());

                            if ($objectData->length > 0) {

                                foreach ($objectData as $key => $value) {

                                    $documentFormFieldItem = clone ($documentFormField);

                                    $objectValue = $value->nodeValue;

                                    if ($metadataObject->getDataType() == \EWW\Dpf\Domain\Model\MetadataObject::INPUT_DATA_TYPE_DATE) {
                                        $dateStr = explode('T', $objectValue);
                                        $date    = date_create_from_format('Y-m-d', trim($dateStr[0]));
                                        if ($date) {
                                            $objectValue = date_format($date, 'd.m.Y');
                                        }
                                    }

                                    $objectValue = str_replace('"', "'", $objectValue);

                                    $documentFormFieldItem->setValue($objectValue, $metadataObject->getDefaultValue());

                                    $documentFormGroupItem->addItem($documentFormFieldItem);
                                }
                            } else {
                                $documentFormGroupItem->addItem($documentFormField);
                            }

                        }

                        $documentFormPage->addItem($documentFormGroupItem);
                    }
                } else {

                    $documentFormGroup->setEmptyGroup(true);

                    foreach ($metadataGroup->getMetadataObject() as $metadataObject) {
                        $documentFormField = new \EWW\Dpf\Domain\Model\DocumentFormField();
                        $documentFormField->setUid($metadataObject->getUid());
                        $documentFormField->setDisplayName($metadataObject->getDisplayName());
                        $documentFormField->setName($metadataObject->getName());
                        $documentFormField->setMandatory($metadataObject->getMandatory());

                        $documentFormField->setAccessRestrictionRoles($metadataObject->getAccessRestrictionRoles());

                        $documentFormField->setConsent($metadataObject->getConsent());
                        $documentFormField->setValidation($metadataObject->getValidation());
                        $documentFormField->setDataType($metadataObject->getDataType());
                        $documentFormField->setMaxIteration($metadataObject->getMaxIteration());
                        $documentFormField->setInputField($metadataObject->getInputField());
                        $documentFormField->setInputOptions($metadataObject->getInputOptionList());
                        $documentFormField->setFillOutService($metadataObject->getFillOutService());
                        $documentFormField->setGndFieldUid($metadataObject->getGndFieldUid());
                        $documentFormField->setMaxInputLength($metadataObject->getMaxInputLength());
                        $documentFormField->setValue("", $metadataObject->getDefaultValue());
                        $documentFormField->setObjectType($metadataObject->getObjectType());

                        $depositLicense = $this->depositLicenseRepository->findByUid($metadataObject->getDepositLicense());
                        $documentFormField->setDepositLicense($depositLicense);

                        $documentFormGroup->addItem($documentFormField);
                    }

                    $documentFormPage->addItem($documentFormGroup);
                }
            }

            $documentForm->addItem($documentFormPage);
        }

        // Files
        $primaryFile = $this->fileRepository->getPrimaryFileByDocument($document);
        $documentForm->setPrimaryFile($primaryFile);

        $secondaryFiles = $this->fileRepository->getSecondaryFilesByDocument($document)->toArray();
        $documentForm->setSecondaryFiles($secondaryFiles);

        return $documentForm;
    }

    public function getDocument($documentForm)
    {
        /** @var Document $document */

        if ($documentForm->getDocumentUid()) {
            $document = $this->documentRepository->findByUid($documentForm->getDocumentUid());
            $tempInternalFormat = new \EWW\Dpf\Helper\InternalFormat($document->getXmlData());
            $fobIdentifiers = $tempInternalFormat->getPersonFisIdentifiers();
        } else {
            $document = $this->objectManager->get(Document::class);
            $fobIdentifiers = [];
        }

        $processNumber = $document->getProcessNumber();
        if (empty($processNumber)) {
            $processNumberGenerator = $this->objectManager->get(ProcessNumberGenerator::class);
            $processNumber = $processNumberGenerator->getProcessNumber();
            $document->setProcessNumber($processNumber);
        }

        $documentType = $this->documentTypeRepository->findByUid($documentForm->getUid());

        $document->setDocumentType($documentType);

        $document->setReservedObjectIdentifier($documentForm->getQucosaId());

        $document->setValid($documentForm->getValid());
        
        if ($documentForm->getComment()) {
            $document->setComment($documentForm->getComment());
        }

        $formMetaData = $this->getMetadata($documentForm);

        $exporter = new \EWW\Dpf\Services\ParserGenerator();

        $exporter->setFileData($document->getFileData());

        $documentData['documentUid'] = $documentForm->getDocumentUid();
        $documentData['metadata']    = $formMetaData['mods'];
        $documentData['files']       = array();

        $exporter->buildXmlFromForm($documentData);

        $internalXml = $exporter->getXmlData();
        $internalFormat = new \EWW\Dpf\Helper\InternalFormat($internalXml);

        // set static xml
        $internalFormat->setDocumentType($documentType->getName());
        $internalFormat->setProcessNumber($processNumber);

        $document->setXmlData($internalFormat->getXml());

        $document->setNewlyAssignedFobIdentifiers(array_diff($internalFormat->getPersonFisIdentifiers(), $fobIdentifiers));

        $document->setTitle($internalFormat->getTitle());
        $document->setEmbargoDate($formMetaData['embargo']);
        $document->setAuthors($internalFormat->getAuthors());
        $document->setDateIssued($internalFormat->getDateIssued());

        return $document;
    }

    public function getMetadata($documentForm)
    {

        foreach ($documentForm->getItems() as $page) {

            foreach ($page[0]->getItems() as $group) {

                foreach ($group as $groupItem) {

                    $item = array();

                    $uid           = $groupItem->getUid();
                    $metadataGroup = $this->metadataGroupRepository->findByUid($uid);

                    $item['mapping'] = $metadataGroup->getRelativeMapping();

                    $item['modsExtensionMapping'] = $metadataGroup->getRelativeModsExtensionMapping();

                    $item['modsExtensionReference'] = trim($metadataGroup->getModsExtensionReference(), " /");

                    $item['groupUid'] = $uid;

                    $fieldValueCount   = 0;
                    $defaultValueCount = 0;
                    $fieldCount        = 0;
                    foreach ($groupItem->getItems() as $field) {
                        foreach ($field as $fieldItem) {
                            $fieldUid       = $fieldItem->getUid();
                            $metadataObject = $this->metadataObjectRepository->findByUid($fieldUid);

                            $fieldMapping = $metadataObject->getRelativeMapping();

                            $formField = array();

                            $value = $fieldItem->getValue();

                            if ($metadataObject->getDataType() == \EWW\Dpf\Domain\Model\MetadataObject::INPUT_DATA_TYPE_DATE) {
                                $date = date_create_from_format('d.m.Y', trim($value));
                                if ($date) {
                                    $value = date_format($date, 'Y-m-d');
                                }
                            }

                            if ($metadataObject->getEmbargo()) {
                                $form['embargo'] = new \DateTime($value);
                            }

                            $fieldCount++;
                            if (!empty($value)) {
                                $fieldValueCount++;
                                $defaultValue = $fieldItem->getHasDefaultValue();
                                if ($fieldItem->getHasDefaultValue()) {
                                    $defaultValueCount++;
                                }
                            }

                            $value = str_replace('"', "'", $value);
                            if ($value) {
                                $formField['modsExtension'] = $metadataObject->getModsExtension();

                                $formField['mapping'] = $fieldMapping;
                                $formField['value']   = $value;

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

                    if ($groupItem->getMandatory() || $defaultValueCount < $fieldValueCount || $defaultValueCount == $fieldCount) {
                        $form['mods'][] = $item;
                    }

                }

            }
        }

        return $form;

    }

}
