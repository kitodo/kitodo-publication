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
     * document
     *
     * @var \EWW\Dpf\Domain\Model\Document
     */
    protected $document;


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
     * @param \EWW\Dpf\Domain\Model\Document $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }


    public function getDeletedFiles()
    {

        $deletedFiles = array();

        if (is_array($this->document->getDeleteFile())) {
            foreach ($this->document->getDeleteFile() as $key => $value) {

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
        $primaryFile = $this->document->getPrimaryFile();

        if ($primaryFile && $primaryFile['error'] != 4) {
            // Use the existing file entry
            $file     = null;
            $document = $this->documentRepository->findByUid($this->document->getUid());
            if ($document) {
                $file = $this->fileRepository->getPrimaryFileByDocument($document);
            }

            $newPrimaryFile = $this->getUploadedFile($primaryFile, true, $file);
            $newPrimaryFile->setLabel($fullTextLabel);

            $newFiles[] = $newPrimaryFile;
        }

        if (is_array($this->document->getPrimFile())) {

            foreach ($this->document->getPrimFile() as $fileId => $fileData) {

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
        if (is_array($this->document->getSecondaryFiles())) {
            foreach ($this->document->getSecondaryFiles() as $tmpFile) {
                if ($tmpFile['error'] != 4) {
                    $f          = $this->getUploadedFile($tmpFile);
                    $newFiles[] = $f;
                }
            }
        }

        if (is_array($this->document->getSecFiles())) {

            foreach ($this->document->getSecFiles() as $fileId => $fileData) {

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
        $primaryFile = $this->document->getPrimaryFile();
        if ($primaryFile && $primaryFile['error'] != 0) {
            return true;
        }

        if (is_array($this->document->getSecondaryFiles())) {
            foreach ($this->document->getSecondaryFiles() as $tmpFile) {
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
}
