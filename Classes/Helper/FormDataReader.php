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

use EWW\Dpf\Domain\Model\DocumentForm;
use EWW\Dpf\Domain\Model\File;
use EWW\Dpf\Domain\Model\FileValidationResults;
use EWW\Dpf\Domain\Model\MetadataObject;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class FormDataReader
{

    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentTypeRepository = null;

    /**
     * metadataPageRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataPageRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $metadataPageRepository = null;

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
     * fileRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FileRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $fileRepository = null;

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentRepository = null;

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
     * depositLicenseRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DepositLicenseRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $depositLicenseRepository = null;

    /**
     * formData
     *
     * @var array
     */
    protected $formData;

    /**
     * documentType
     *
     * @var
     */
    protected $documentType;

    /**
     * uploadPath
     *
     * @var
     */
    protected $uploadPath;

    /**
     * basePath
     *
     * @var
     */
    protected $uploadBaseUrl;

    public function __construct()
    {

        $uploadFileUrl = new \EWW\Dpf\Helper\UploadFileUrl;

        $this->uploadBaseUrl = $uploadFileUrl->getUploadUrl() . "/";

        $this->uploadPath = Environment::getPublicPath() . "/" . $uploadFileUrl->getDirectory() . "/";

    }

    /**
     *
     * @param array $formData
     */
    public function setFormData($formData)
    {
        $this->formData     = $formData;
        $this->documentType = $this->documentTypeRepository->findByUid($formData['type']);
    }

    protected function getFields()
    {
        $fields = array();

        if (is_array($this->formData['metadata'])) {

            foreach ($this->formData['metadata'] as $key => $value) {
                if (is_array($value)) {
                    $formField = new \EWW\Dpf\Helper\FormField($key, array_shift($value));
                    $fields[]  = $formField;
                } else {
                    $formField = new \EWW\Dpf\Helper\FormField($key, $value);
                    $fields[]  = $formField;
                }
            }
        }

        return $fields;
    }

    protected function getDeletedFiles()
    {
        $deletedFiles = array();

        if (is_array($this->formData['deleteFile'])) {
            foreach ($this->formData['deleteFile'] as $key => $value) {

                $file = $this->fileRepository->findByUid($value);

                // Deleting the primary file is not allowed.
                // if (!$file->isPrimaryFile()) {
                //    $deletedFiles[] = $file;
                // }

                $deletedFiles[] = $file;
            }
        }

        return $deletedFiles;
    }

    /**
     * @return array
     */
    public function uploadErrors()
    {
        $errorFiles = [];

        if (is_array($this->formData) && array_key_exists('metadata', $this->formData)) {
            foreach ($this->formData['metadata'] as $metadata) {
                if (is_array($metadata) && array_key_exists('file', $metadata)) {
                    if (
                        $metadata['file']['error'] != UPLOAD_ERR_OK &&
                        $metadata['file']['error'] != UPLOAD_ERR_NO_FILE
                    ) {
                        $errorFiles[] = $metadata['file']['name'];
                    }
                }
            }
        }

        return $errorFiles;
    }

    protected function getUrlFile($fileUrl, $primary = false, \EWW\Dpf\Domain\Model\File $file = null)
    {
        if (empty($file)) {
            $file = $this->objectManager->get(File::class);
        }

        $fileName = uniqid(time(), true);

        # get remote mimetype
        $ch = curl_init($fileUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch,CURLOPT_MAXREDIRS,4);
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        if ($httpCode == 200) {
            $file->setContentType($contentType);
        } else {
            $file->setContentType('application/octet-stream');
        }

        $path_parts = pathinfo($fileUrl);
        $origFilename = $path_parts['filename']? $path_parts['filename'] : 'unknown-file-name';
        $origFilename .= $path_parts['extension']? '.'.$path_parts['extension'] : '';
        $file->setTitle($origFilename);

        $file->setLink($fileUrl);
        $file->setValidationResults(new FileValidationResults());
        $file->setPrimaryFile($primary);
        $file->setFileIdentifier(uniqid(time(), true));

        if ($primary) {
            if ($file->getDatastreamIdentifier()) {
                $file->setStatus(\EWW\Dpf\Domain\Model\File::STATUS_CHANGED);
            } else {
                $file->setStatus(\EWW\Dpf\Domain\Model\File::STATUS_ADDED);
            }
        } else {
            $file->setStatus(\EWW\Dpf\Domain\Model\File::STATUS_ADDED);
        }

        return $file;
    }

    protected function getUploadedFile($tmpFile, $primary = false, \EWW\Dpf\Domain\Model\File $file = null)
    {

        if (empty($file)) {
            $file = $this->objectManager->get(File::class);
        }

        $fileName = uniqid(time(), true);

        \TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move($tmpFile['tmp_name'], $this->uploadPath . $fileName);

        $finfo       = finfo_open(FILEINFO_MIME_TYPE);

        $contentType = '';

        if (file_exists($this->uploadPath . $fileName)) {
            $contentType = finfo_file($finfo, $this->uploadPath . $fileName);
        }

        finfo_close($finfo);

        $file->setContentType($contentType);

        $file->setTitle($tmpFile['name']);
        $file->setLink($fileName);
        $file->setValidationResults(new FileValidationResults());
        $file->setPrimaryFile($primary);
        $file->setFileIdentifier(uniqid(time(), true));

        if ($primary) {
            if ($file->getDatastreamIdentifier()) {
                $file->setStatus(\EWW\Dpf\Domain\Model\File::STATUS_CHANGED);
            } else {
                $file->setStatus(\EWW\Dpf\Domain\Model\File::STATUS_ADDED);
            }
        } else {
            $file->setStatus(\EWW\Dpf\Domain\Model\File::STATUS_ADDED);
        }

        return $file;
    }

    public function getDocumentForm()
    {
        $fields = $this->getFields();

        $documentForm = new \EWW\Dpf\Domain\Model\DocumentForm();
        $documentForm->setCsrfToken($this->formData['csrfToken']);
        $documentForm->setUid($this->documentType->getUid());
        $documentForm->setDisplayName($this->documentType->getDisplayName());
        $documentForm->setName($this->documentType->getName());
        $documentForm->setDocumentUid($this->formData['documentUid']);
        $documentForm->setFedoraPid($this->formData['fedoraPid']);
        $documentForm->setReservedFedoraPid($this->formData['reservedFedoraPid']);
        $documentForm->setValid(!empty($this->formData['validDocument']));
        if ($this->formData['comment']) {
            $documentForm->setComment($this->formData['comment']);
        }

        $documentData = array();

        foreach ($fields as $field) {
            $pageUid    = $field->getPageUid();
            $groupUid   = $field->getGroupUid();
            $groupIndex = $field->getGroupIndex();
            $fieldUid   = $field->getFieldUid();
            $fieldIndex = $field->getFieldIndex();
            $value      = $field->getValue();

            $documentData[$pageUid][$groupUid][$groupIndex][$fieldUid][$fieldIndex] = $value;
        }

        foreach ($documentData as $pageUid => $page) {
            $metadataPage     = $this->metadataPageRepository->findByUid($pageUid);
            $documentFormPage = new \EWW\Dpf\Domain\Model\DocumentFormPage();
            $documentFormPage->setUid($metadataPage->getUid());
            $documentFormPage->setDisplayName($metadataPage->getDisplayName());
            $documentFormPage->setName($metadataPage->getName());

            $documentFormPage->setAccessRestrictionRoles($metadataPage->getAccessRestrictionRoles());

            foreach ($page as $groupUid => $groupItem) {
                foreach ($groupItem as $groupItemIndex => $group) {
                    $metadataGroup     = $this->metadataGroupRepository->findByUid($groupUid);
                    $documentFormGroup = new \EWW\Dpf\Domain\Model\DocumentFormGroup();
                    $documentFormGroup->setUid($metadataGroup->getUid());
                    $documentFormGroup->setDisplayName($metadataGroup->getDisplayName());
                    $documentFormGroup->setName($metadataGroup->getName());
                    $documentFormGroup->setMandatory($metadataGroup->getMandatory());

                    $documentFormGroup->setAccessRestrictionRoles($metadataGroup->getAccessRestrictionRoles());

                    $documentFormGroup->setInfoText($metadataGroup->getInfoText());
                    $documentFormGroup->setGroupType($metadataGroup->getGroupType());
                    $documentFormGroup->setMaxIteration($metadataGroup->getMaxIteration());

                    $fileLabel = "";
                    $fileDownload = "";
                    $fileArchive = "";

                    $fileIdentifier = '';
                    if (array_key_exists('fileIdentifier', $group)) {
                        $fileIdentifier = array_shift($group['fileIdentifier']);
                        unset($group['fileIdentifier']);
                    }

                    // Needed for the suggestion compare feature
                    $documentFormGroup->setId($groupUid."-".$groupItemIndex);

                    foreach ($group as $objectUid => $objectItem) {

                        foreach ($objectItem as $objectItemIndex => $object) {

                            /** @var MetadataObject $metadataObject */
                            $metadataObject    = $this->metadataObjectRepository->findByUid($objectUid);

                            /** @var DocumentForm $documentFormField */
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
                            $documentFormField->setInputOptionList($metadataObject->getInputOptionList());
                            $documentFormField->setInputField($metadataObject->getInputField());
                            $documentFormField->setFillOutService($metadataObject->getFillOutService());
                            $documentFormField->setGndFieldUid($metadataObject->getGndFieldUid());
                            $documentFormField->setMaxInputLength($metadataObject->getMaxInputLength());
                            $documentFormField->setValue($object, $metadataObject->getDefaultValue());
                            $depositLicense = $this->depositLicenseRepository->findByUid($metadataObject->getDepositLicense());
                            $documentFormField->setDepositLicense($depositLicense);

                            $documentFormField->setId($groupUid . "-" . $groupItemIndex . "-" . $objectUid . "-" . $objectItemIndex);

                            $documentFormGroup->addItem($documentFormField);

                            if ($metadataGroup->isFileGroup()) {

                                // Use the existing file entry
                                $file = null;
                                $document = $this->documentRepository->findByUid($this->formData['documentUid']);
                                if ($document) {
                                    if ($metadataGroup->isPrimaryFileGroup()) {
                                        $file = $document->getPrimaryFile();
                                    } else {
                                        $file = $document->getFileByFileIdentifier($fileIdentifier);
                                    }
                                }

                                if ($metadataObject->isUploadField() ) {
                                    if ($object && is_array($object) &&
                                        array_key_exists('error', $object) &&
                                        $object['error'] != UPLOAD_ERR_NO_FILE)
                                    {
                                        if (empty($file)) {
                                            $file = $this->objectManager->get(File::class);
                                            $file = $this->getUploadedFile(
                                                $object,
                                                $metadataGroup->isPrimaryFileGroup(),
                                                $file);
                                            $documentFormField->setFile($file);
                                        } else {
                                            $file = $this->getUploadedFile(
                                                $object,
                                                $metadataGroup->isPrimaryFileGroup(),
                                                $file);
                                            $documentFormField->setFile($file);
                                        }

                                        $documentFormField->setValue($file->getLink());
                                        $fileIdentifier = $file->getFileIdentifier();
                                        $documentForm->addFile($file);

                                    } elseif ($object && !is_array($object)) {

                                        if (empty($file) || $object != $file->getLink()) {
                                            $file = $this->getUrlFile(
                                                $object,
                                                $metadataGroup->isPrimaryFileGroup(),
                                                $file
                                            );
                                        }

                                        $documentFormField->setFile($file);
                                        $fileIdentifier = $file->getFileIdentifier();
                                        $documentForm->addFile($file);
                                    }
                                } else {
                                    if ($metadataObject->isFileLabelField()) {
                                        $fileLabel = $object;
                                    }

                                    if ($metadataObject->isFileDownloadField()) {
                                        $fileDownload = !empty($object);
                                    }

                                    if ($metadataObject->isFileArchiveField()) {
                                        $fileArchive = !empty($object);
                                    }

                                }

                                if ($file) {
                                    $file->setLabel($fileLabel);
                                    $file->setDownload($fileDownload);
                                    $file->setArchive($fileArchive);
                                }

                            }
                        }
                    }

                    if (!$metadataGroup->isFileGroup() || $fileIdentifier) {
                        $documentFormPage->addItem($documentFormGroup);
                    }
                }
            }

            $documentForm->addItem($documentFormPage);
        }

        return $documentForm;
    }
}
