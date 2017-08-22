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

class FileUploadMapper
{

    /**
     * fileRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FileRepository
     * @inject
     */
    protected $fileRepository = null;

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository = null;

    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @inject
     */
    protected $objectManager;

    /**
     *
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     * @inject
     */
    protected $configurationManager;

    /**
     * formData
     *
     * @var array
     */
    protected $formData;


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

        $this->uploadPath = PATH_site . $uploadFileUrl->getDirectory() . "/";

    }

    /**
     *
     * @param array $formData
     */
    public function setFormData($formData)
    {
        $this->formData     = $formData;
    }


    public function getDeletedFiles()
    {

        $deletedFiles = array();

        if (is_array($this->formData['deleteFile'])) {
            foreach ($this->formData['deleteFile'] as $key => $value) {

                $file = $this->fileRepository->findByUid($value);

                // Deleting the primary file is not allowed.
                if (!$file->isPrimaryFile()) {
                    $deletedFiles[] = $file;
                }

            }
        }

        return $deletedFiles;
    }

    public function getNewAndUpdatedFiles()
    {

        $frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        $fullTextLabel          = $frameworkConfiguration['settings']['defaultValue']['fullTextLabel'];

        $newFiles = array();

        // Primary file
        if ($this->formData['primaryFile'] && $this->formData['primaryFile']['error'] != 4) {

            // Use the existing file entry
            $file     = null;
            $document = $this->documentRepository->findByUid($this->formData['documentUid']);
            if ($document) {
                $file = $this->fileRepository->getPrimaryFileByDocument($document);
            }

            $newPrimaryFile = $this->getUploadedFile($this->formData['primaryFile'], true, $file);
            $newPrimaryFile->setLabel($fullTextLabel);

            $newFiles[] = $newPrimaryFile;
        }

        if (is_array($this->formData['primFile'])) {

            foreach ($this->formData['primFile'] as $fileId => $fileData) {

                $file       = $this->fileRepository->findByUID($fileId);
                $fileStatus = $file->getStatus();

                if (empty($fileData['label'])) {
                    $fileData['label'] = $fullTextLabel;
                }

                if ($file->getLabel() != $fileData['label'] ||
                    $file->getDownload() != !empty($fileData['download']) ||
                    $file->getArchive() != !empty($fileData['archive'])) {

                    $file->setLabel($fileData['label']);
                    $file->setDownload(!empty($fileData['download']));
                    $file->setArchive(!empty($fileData['archive']));

                    if (empty($fileStatus)) {
                        $file->setStatus(\EWW\Dpf\Domain\Model\File::STATUS_CHANGED);
                    }

                    $newFiles[] = $file;
                }
            }

        }

        // Secondary files
        if (is_array($this->formData['secondaryFiles'])) {
            foreach ($this->formData['secondaryFiles'] as $tmpFile) {
                if ($tmpFile['error'] != 4) {
                    $f          = $this->getUploadedFile($tmpFile);
                    $newFiles[] = $f;
                }
            }
        }

        if (is_array($this->formData['secFiles'])) {

            foreach ($this->formData['secFiles'] as $fileId => $fileData) {

                $file       = $this->fileRepository->findByUID($fileId);
                $fileStatus = $file->getStatus();

                if ($file->getLabel() != $fileData['label'] ||
                    $file->getDownload() != !empty($fileData['download']) ||
                    $file->getArchive() != !empty($fileData['archive'])) {

                    $file->setLabel($fileData['label']);
                    $file->setDownload(!empty($fileData['download']));
                    $file->setArchive(!empty($fileData['archive']));

                    if (empty($fileStatus)) {
                        $file->setStatus(\EWW\Dpf\Domain\Model\File::STATUS_CHANGED);
                    }

                    $newFiles[] = $file;
                }
            }

        }

        return $newFiles;

    }

    public function uploadError()
    {

        if ($this->formData['primaryFile'] && $this->formData['primaryFile']['error'] != 0) {
            return true;
        }

        if (is_array($this->formData['secondaryFiles'])) {
            foreach ($this->formData['secondaryFiles'] as $tmpFile) {
                if ($tmpFile['error'] != 0 && $tmpFile['error'] != 4) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function getUploadedFile($tmpFile, $primary = false, \EWW\Dpf\Domain\Model\File $file = null)
    {

        if (empty($file)) {
            $file = $this->objectManager->get('EWW\Dpf\Domain\Model\File');
        }

        $fileName = uniqid(time(), true);

        \TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move($tmpFile['tmp_name'], $this->uploadPath . $fileName);

        $finfo       = finfo_open(FILEINFO_MIME_TYPE);
        $contentType = finfo_file($finfo, $this->uploadPath . $fileName);
        finfo_close($finfo);

        $file->setContentType($contentType);

        $file->setTitle($tmpFile['name']);
        $file->setLabel($tmpFile['label']);
        $file->setDownload(!empty($tmpFile['download']));
        $file->setArchive(!empty($tmpFile['archive']));
        $file->setLink($this->uploadBaseUrl . $fileName);
        $file->setPrimaryFile($primary);

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


/*
        $documentForm->setDeletedFiles($this->getDeletedFiles());

        $documentForm->setNewFiles($this->getNewAndUpdatedFiles());

        return $documentForm;
*/

}
