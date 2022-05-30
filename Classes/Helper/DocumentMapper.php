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

use EWW\Dpf\Domain\Model\MetadataGroup;
use EWW\Dpf\Domain\Model\DocumentForm;
use EWW\Dpf\Domain\Model\File;
use EWW\Dpf\Domain\Model\MetadataObject;
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
     * clientPid
     *
     * @var int
     */
    protected $clientPid = 0;

    /**
     * @var bool
     */
    protected $customClientPid = false;

    /**
     * clientConfigurationManager
     *
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $clientConfigurationManager;

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
        $documentForm->generateCsrfToken();
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

        $fedoraPid = $document->getObjectIdentifier();

        if (empty($fedoraPid)) {
            $fedoraPid = $document->getProcessNumber();
        }

        $documentForm->setFedoraPid($fedoraPid);

        $internalFormat = new \EWW\Dpf\Helper\InternalFormat($document->getXmlData(), $this->clientPid);

        $excludeGroupAttributes = array();

        foreach ($document->getDocumentType()->getMetadataPage() as $metadataPage) {
            $documentFormPage = new \EWW\Dpf\Domain\Model\DocumentFormPage();
            $documentFormPage->setUid($metadataPage->getUid());
            $documentFormPage->setDisplayName($metadataPage->getDisplayName());
            $documentFormPage->setName($metadataPage->getName());

            $documentFormPage->setAccessRestrictionRoles($metadataPage->getAccessRestrictionRoles());

            foreach ($metadataPage->getMetadataGroup() as $metadataGroup) {
                /** @var MetadataGroup $metadataGroup */

                $documentFormGroup = new \EWW\Dpf\Domain\Model\DocumentFormGroup();
                $documentFormGroup->setUid($metadataGroup->getUid());
                $documentFormGroup->setDisplayName($metadataGroup->getDisplayName());
                $documentFormGroup->setName($metadataGroup->getName());
                $documentFormGroup->setMandatory($metadataGroup->getMandatory());

                $documentFormGroup->setAccessRestrictionRoles($metadataGroup->getAccessRestrictionRoles());

                $documentFormGroup->setInfoText($metadataGroup->getInfoText());
                $documentFormGroup->setGroupType($metadataGroup->getGroupType());
                $documentFormGroup->setMaxIteration($metadataGroup->getMaxIteration());

                $documentFormGroup->setOptionalGroups($metadataGroup->getOptionalGroups());
                $documentFormGroup->setRequiredGroups($metadataGroup->getRequiredGroups());

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

                    $groupItemIdIndex = -1;

                    foreach ($groupData as $key => $data) {

                        $documentFormGroupItem = clone ($documentFormGroup);

                        // Needed for the suggestion compare feature
                        $groupItemId = $data->getAttribute("metadata-item-id");

                        if (empty($groupItemId)) {
                            $groupItemId = $metadataGroup->getUid() . '-' . ($groupItemIdIndex+1);
                        } else {
                            $groupItemIdParts = explode('-', $groupItemId);
                            $groupItemIdIndex = $groupItemIdParts[1];
                        }

                        $documentFormGroupItem->setId($groupItemId);

                        foreach ($metadataGroup->getMetadataObject() as $metadataObject) {

                            $documentFormField = new \EWW\Dpf\Domain\Model\DocumentFormField();
                            $documentFormField->setUid($metadataObject->getUid());
                            $documentFormField->setDisplayName($metadataObject->getDisplayName());
                            $documentFormField->setName($metadataObject->getName());
                            $documentFormField->setMandatory($metadataObject->getMandatory());

                            $documentFormField->setAccessRestrictionRoles($metadataObject->getAccessRestrictionRoles());

                            $documentFormField->setConsent($metadataObject->getConsent());
                            $documentFormField->setValidation($metadataObject->getValidation());
                            $documentFormField->setValidationErrorMessage($metadataObject->getValidationErrorMessage());
                            $documentFormField->setValidator($metadataObject->getValidator());
                            $documentFormField->setMaxIteration($metadataObject->getMaxIteration());
                            $documentFormField->setInputField($metadataObject->getInputField());
                            $documentFormField->setInputOptionlist($metadataObject->getInputOptionList());
                            $documentFormField->setFillOutService($metadataObject->getFillOutService());
                            $documentFormField->setGndFieldUid($metadataObject->getGndFieldUid());
                            $documentFormField->setMaxInputLength($metadataObject->getMaxInputLength());
                            $documentFormField->setObjectType($metadataObject->getObjectType());

                            $depositLicense = $this->depositLicenseRepository->findByUid($metadataObject->getDepositLicense());
                            $documentFormField->setDepositLicense($depositLicense);

                            if ($metadataObject->getLicenceOptions()) {
                                $licenceOptions = $this->depositLicenseRepository->findByUidList(
                                    explode(",", $metadataObject->getLicenceOptions())
                                );
                                $documentFormField->setLicenceOptions($licenceOptions);
                            }

                            $documentFormField->setHelpText($metadataObject->getHelpText());

                            $objectMapping = "";

                            preg_match_all('/([A-Za-z0-9]+:[A-Za-z0-9]+(\[.*\])*|[A-Za-z0-9:@\.]+)/', $metadataObject->getRelativeMapping(), $objectMappingPath);
                            $objectMappingPath = $objectMappingPath[0];

                            foreach ($objectMappingPath as $key => $value) {

                                // ensure that e.g. <mods:detail> and <mods:detail type="volume">
                                // are not recognized as the same node
                                if ((strpos($value, "@") === false) && ($value != '.')) {
                                    $objectMappingPath[$key] .= "[not(@*) or @metadata-item-id]";
                                }
                            }

                            $objectMapping = implode("/", $objectMappingPath);

                            if ($objectMapping == '[not(@*) or @metadata-item-id]' || empty($objectMappingPath)) {
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

                                $fieldItemIdIndex = -1;

                                foreach ($objectData as $key => $value) {

                                    $documentFormFieldItem = clone ($documentFormField);

                                    $objectValue = $value->nodeValue;

                                    if ($metadataObject->getValidator() == \EWW\Dpf\Domain\Model\MetadataObject::VALIDATOR_DATE) {
                                        $dateStr = explode('T', $objectValue);
                                        $date    = date_create_from_format('Y-m-d', trim($dateStr[0]));
                                        if ($date) {
                                            $objectValue = date_format($date, 'd.m.Y');
                                        }
                                    }

                                    $objectValue = str_replace('"', "'", $objectValue);

                                    $documentFormFieldItem->setValue($objectValue, $metadataObject->getDefaultValue());

                                    if ($value instanceof \DOMAttr) {
                                        $documentFormFieldItem->setId($groupItemId . '-' . $metadataObject->getUid(). '-' . 0);
                                    } else {
                                        $fieldItemId = $value->getAttribute("metadata-item-id");
                                        if ($objectMapping == '.') {
                                            $fieldItemId = $groupItemId . '-' . $metadataObject->getUid(). '-' . 0;
                                        }

                                       if (empty($fieldItemId)) {
                                           $fieldItemId = $groupItemId . '-' . $metadataObject->getUid(). '-' . ($fieldItemIdIndex + 1);
                                       } else {
                                           $fieldItemIdParts = explode('-', $fieldItemId);
                                           $fieldItemIdIndex = $fieldItemIdParts[3];
                                       }

                                       $documentFormFieldItem->setId($fieldItemId);
                                    }

                                    if ($metadataGroup->isFileGroup() && $metadataObject->isUploadField()) {

                                        $fileIdentifier = '';
                                        $fileIdXpath = $this->clientConfigurationManager->getFileIdXpath();
                                        $fileIdentifierNode = $xpath->query($fileIdXpath, $data);
                                        if ($fileIdentifierNode->length > 0) {
                                            $fileIdentifier = $fileIdentifierNode->item(0)->nodeValue;
                                        }

                                        if ($fileIdentifier) {
                                            $file = $document->getFileByFileIdentifier($fileIdentifier);
                                            if ($file) {
                                                $documentFormFieldItem->setFile($file);
                                                $documentForm->addFile($file);
                                            }
                                        }
                                    }

                                    $documentFormGroupItem->addItem($documentFormFieldItem);
                                }
                            } else {
                                $documentFormField->setId($groupItemId . '-' . $metadataObject->getUid(). '-' . 0);
                                $documentFormGroupItem->addItem($documentFormField);
                            }

                        }

                        $documentFormPage->addItem($documentFormGroupItem);
                    }
                } else {

                    $documentFormGroup->setEmptyGroup(true);

                    // Needed for the suggestion compare feature
                    $documentFormGroup->setId($metadataGroup->getUid() . '-' . 0);

                    foreach ($metadataGroup->getMetadataObject() as $metadataObject) {
                        $documentFormField = new \EWW\Dpf\Domain\Model\DocumentFormField();
                        $documentFormField->setUid($metadataObject->getUid());
                        $documentFormField->setDisplayName($metadataObject->getDisplayName());
                        $documentFormField->setName($metadataObject->getName());
                        $documentFormField->setMandatory($metadataObject->getMandatory());

                        $documentFormField->setAccessRestrictionRoles($metadataObject->getAccessRestrictionRoles());

                        $documentFormField->setConsent($metadataObject->getConsent());
                        $documentFormField->setValidation($metadataObject->getValidation());
                        $documentFormField->setValidationErrorMessage($metadataObject->getValidationErrorMessage());
                        $documentFormField->setValidator($metadataObject->getValidator());
                        $documentFormField->setMaxIteration($metadataObject->getMaxIteration());
                        $documentFormField->setInputField($metadataObject->getInputField());
                        $documentFormField->setInputOptionList($metadataObject->getInputOptionList());
                        $documentFormField->setFillOutService($metadataObject->getFillOutService());
                        $documentFormField->setGndFieldUid($metadataObject->getGndFieldUid());
                        $documentFormField->setMaxInputLength($metadataObject->getMaxInputLength());
                        $documentFormField->setValue("", $metadataObject->getDefaultValue());
                        $documentFormField->setObjectType($metadataObject->getObjectType());

                        $depositLicense = $this->depositLicenseRepository->findByUid($metadataObject->getDepositLicense());
                        $documentFormField->setDepositLicense($depositLicense);

                        if ($metadataObject->getLicenceOptions()) {
                            $licenceOptions = $this->depositLicenseRepository->findByUidList(
                                explode(",", $metadataObject->getLicenceOptions())
                            );
                            $documentFormField->setLicenceOptions($licenceOptions);
                        }

                        $documentFormField->setHelpText($metadataObject->getHelpText());

                        $documentFormField->setId(
                            $metadataGroup->getUid() . '-' . 0 . '-' . $metadataObject->getUid(). '-' . 0
                        );

                        $documentFormGroup->addItem($documentFormField);
                    }

                    $documentFormPage->addItem($documentFormGroup);
                }
            }

            $documentForm->addItem($documentFormPage);
        }

        return $documentForm;
    }

    public function getDocument($documentForm)
    {
        /** @var Document $document */

        if ($documentForm->getDocumentUid()) {
            if ($this->isCustomClientPid()) {
                $this->documentRepository->crossClient(true);
            }
            $document = $this->documentRepository->findByUid($documentForm->getDocumentUid());
            $tempInternalFormat = new \EWW\Dpf\Helper\InternalFormat($document->getXmlData(), $this->clientPid);
            $fobIdentifiers = $tempInternalFormat->getPersonFisIdentifiers();
        } else {
            $document = $this->objectManager->get(Document::class);
            $fobIdentifiers = [];
        }

        $processNumber = $document->getProcessNumber();
        if (empty($processNumber)) {
            $reservedFedoraPid = $documentForm->getReservedFedoraPid();
            if (empty($reservedFedoraPid)) {
                $processNumberGenerator = $this->objectManager->get(ProcessNumberGenerator::class);
                $processNumber = $processNumberGenerator->getProcessNumber();
                $document->setProcessNumber($processNumber);
            } else {
                $document->setProcessNumber($reservedFedoraPid);
            }
        }

        $documentType = $this->documentTypeRepository->findByUid($documentForm->getUid());

        $document->setDocumentType($documentType);

        $document->setValid($documentForm->getValid());

        if ($documentForm->getComment()) {
            $document->setComment($documentForm->getComment());
        }

        $formMetaData = $this->getMetadata($documentForm);

        $exporter = new \EWW\Dpf\Services\ParserGenerator($this->clientPid);

        $documentData['documentUid'] = $documentForm->getDocumentUid();
        $documentData['metadata']    = $formMetaData['mods'];

        $exporter->buildXmlFromForm($documentData);

        $internalXml = $exporter->getXmlData();
        $internalFormat = new \EWW\Dpf\Helper\InternalFormat($internalXml, $this->clientPid);

        // set static xml
        $internalFormat->setDocumentType($documentType->getName());
        $internalFormat->setProcessNumber($processNumber);

        $document = $this->updateFiles($document, $documentForm->getFiles());
        $document->setXmlData($internalFormat->getXml());

        $document->setNewlyAssignedFobIdentifiers(array_diff($internalFormat->getPersonFisIdentifiers(), $fobIdentifiers));

        $document->setTitle($internalFormat->getTitle());
        $document->setEmbargoDate($formMetaData['embargo']);
        $document->setAuthors($internalFormat->getAuthors());
        $document->setDateIssued($internalFormat->getDateIssued());

        return $document;
    }

    /**
     * @param DocumentForm $documentForm
     * @return array
     * @throws \Exception
     */
    public function getMetadata(DocumentForm $documentForm)
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

                    $item['groupType'] = $metadataGroup->getGroupType();

                    $fieldValueCount   = 0;
                    $defaultValueCount = 0;
                    $fieldCount        = 0;

                    // ID for the metadata group, used for the suggestion feature to optimize the compare between
                    // a suggestion and its original document.
                   $item['attributes'][] = [
                        'mapping' => '@metadata-item-id',
                        'value'   => $groupItem->getId()
                   ];

                    foreach ($groupItem->getItems() as $field) {
                        foreach ($field as $fieldItem) {

                            $fieldUid       = $fieldItem->getUid();
                            $metadataObject = $this->metadataObjectRepository->findByUid($fieldUid);

                            $fieldMapping = $metadataObject->getRelativeMapping();

                            $formField = array();

                            $value = $fieldItem->getValue();

                            if ($metadataObject->getValidator() == \EWW\Dpf\Domain\Model\MetadataObject::VALIDATOR_DATE) {
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

                            $file = $fieldItem->getFile();
                            $value = str_replace('"', "'", $value);
                            if ($value || $file) {
                                $formField['modsExtension'] = $metadataObject->getModsExtension();

                                $formField['mapping'] = $fieldMapping;
                                $formField['value']   = $value;

                                // ID for the metadata field, used for the suggestion feature to optimize the compare between
                                // a suggestion and its original document.
                                $formField['id'] = $fieldItem->getId();

                                if (strpos($fieldMapping, "@") === 0) {
                                    $item['attributes'][] = $formField;
                                } else {
                                    $item['values'][] = $formField;
                                }

                                $fileIdXpath = $this->clientConfigurationManager->getFileIdXpath();
                                if ($file) {
                                    $item['values'][] = [
                                        'mapping' => $fileIdXpath,
                                        'value'   => $file->getFileIdentifier(),
                                        // ID for the metadata field, used for the suggestion feature to optimize the compare between
                                        // a suggestion and its original document.
                                        'id' => $groupItem->getId() . "-id-0"
                                    ];
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

    /**
     * @return int
     */
    public function getClientPid(): int
    {
        return $this->clientPid;
    }

    /**
     * @param int $clientPid
     */
    public function setClientPid(int $clientPid): void
    {
        $this->customClientPid = true;
        $this->clientPid = $clientPid;
    }

    /**
     * @return bool
     */
    public function isCustomClientPid(): bool
    {
        return $this->customClientPid;
    }

    /**
     * Adds and delete file model objects attached to the document.
     *
     * @param Document $document
     * @param array $files
     * @return Document
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\UnknownObjectException
     */
    protected function updateFiles(Document $document, $files)
    {
        if ($document->isSuggestion()) {
            if (is_array($files)) {
                foreach ($files as $file) {
                    $newFile = new File();
                    $newFile = $newFile->copy($file);
                    $document->addFile($newFile);
                }
            }
            return $document;
        }

        $filesToBeDeleted = [];
        foreach ($document->getFile() as $docFile) {
            $filesToBeDeleted[$docFile->getFileIdentifier()] = $docFile;
        }
        // Add or update files
        /** @var File $file */
        if (is_array($files)) {
            foreach ($files as $file) {
                if ($file->getUid()) {
                    $this->fileRepository->update($file);
                } else {
                    $this->fileRepository->add($file);
                    $document->addFile($file);
                }

                if (array_key_exists($file->getFileIdentifier(), $filesToBeDeleted)) {
                    unset($filesToBeDeleted[$file->getFileIdentifier()]);
                }
            }
        }

        // Delete files
        /** @var File $deleteFile */
        foreach ($filesToBeDeleted as $fileToBeDeleted) {
            if (trim($fileToBeDeleted->getDatastreamIdentifier())) {
                $fileToBeDeleted->setStatus(File::STATUS_DELETED);
                $this->fileRepository->update($fileToBeDeleted);
            } else {
                $document->removeFile($fileToBeDeleted);
            }
        }

        return $document;
    }

}
