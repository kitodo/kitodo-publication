<?php
namespace EWW\Dpf\Domain\Model;

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

/**
 * Document
 */
class Document extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * title
     *
     * @var string
     */
    protected $title = '';

    /**
     * authors
     *
     * @var string
     */
    protected $authors = '';

    /**
     * xmlData
     *
     * @var string
     */
    protected $xmlData = '';

    /**
     * slubInfoData
     *
     * @var string
     */
    protected $slubInfoData = '';

    /**
     * documentType
     *
     * @var \EWW\Dpf\Domain\Model\DocumentType
     */
    protected $documentType = null;

    /**
     * objectIdentifier
     *
     * @var string
     */
    protected $objectIdentifier;

    /**
     * reservedObjectIdentifier
     *
     * @var string
     */
    protected $reservedObjectIdentifier;

    /**
     * state
     *
     * @var string
     */
    protected $state = self::OBJECT_STATE_NEW;

    /**
     * transferStatus
     *
     * @var string
     */
    protected $transferStatus;

    /**
     *  transferDate
     *
     *  @var integer
     */
    protected $transferDate;

    /**
     * changed
     *
     * @var boolean
     */
    protected $changed = false;

    /**
     * valid
     *
     * @var boolean
     */
    protected $valid = false;

    /**
     *
     * @var string $dateIssued
     */
    protected $dateIssued;

    /**
     *
     * @var string $processNumber
     */
    protected $processNumber;

    /**
     *
     * @var string $metadata;
     */
    protected $metadata = '';


    /**
     *
     * @var array $newFiles;
     */
    protected $newFiles;

    /**
     *
     * @var array $deletedFiles;
     */
    protected $deletedFiles;


    /**
     * file
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\File>
     * @cascade remove
     */
    protected $file = null;

    const TRANSFER_ERROR  = "ERROR";
    const TRANSFER_QUEUED = "QUEUED";
    const TRANSFER_SENT   = "SENT";

    const OBJECT_STATE_NEW             = "NEW";
    const OBJECT_STATE_ACTIVE          = "ACTIVE";
    const OBJECT_STATE_INACTIVE        = "INACTIVE";
    const OBJECT_STATE_DELETED         = "DELETED";
    const OBJECT_STATE_LOCALLY_DELETED = "LOCALLY_DELETED";

    /**
     * __construct
     */
    public function __construct()
    {
        //Do not remove the next line: It would break the functionality
        $this->initStorageObjects();
    }

    /**
     * Initializes all ObjectStorage properties
     * Do not modify this method!
     * It will be rewritten on each save in the extension builder
     * You may modify the constructor of this class instead
     *
     * @return void
     */
    protected function initStorageObjects()
    {
        $this->file = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Returns the title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title
     *
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->title = $title;
        //htmlspecialchars_decode($title,ENT_QUOTES);
    }

    /**
     * Returns the authors
     *
     * @return array $authors
     */
    public function getAuthors()
    {
        return array_map('trim', explode(";", $this->authors));
    }

    /**
     * Sets the authors
     *
     * @param array $authors
     * @return void
     */
    public function setAuthors($authors)
    {
        $authors       = implode("; ", $authors);
        $this->authors = $authors;
        //htmlspecialchars_decode($authors,ENT_QUOTES);
    }

    /**
     * Returns the xmlData
     *
     * @return string $xmlData
     */
    public function getXmlData()
    {
        return $this->xmlData;
    }

    /**
     * Sets the xmlData
     *
     * @param string $xmlData
     * @return void
     */
    public function setXmlData($xmlData)
    {
        $this->xmlData = $xmlData;
    }

    /**
     * Returns the slubInfoData
     *
     * @return string $slubInfoData
     */
    public function getSlubInfoData()
    {
        return $this->slubInfoData;
    }

    /**
     * Sets the slubInfoData
     *
     * @return string $slubInfoData
     */
    public function setSlubInfoData($slubInfoData)
    {
        $this->slubInfoData = $slubInfoData;
    }

    /**
     * Returns the documentType
     *
     * @return \EWW\Dpf\Domain\Model\DocumentType $documentType
     */
    public function getDocumentType()
    {
        return $this->documentType;
    }

    /**
     * Sets the documentType
     *
     * @param \EWW\Dpf\Domain\Model\DocumentType $documentType
     * @return void
     */
    public function setDocumentType(\EWW\Dpf\Domain\Model\DocumentType $documentType)
    {
        $this->documentType = $documentType;
    }

    /**
     * Returns the objectIdentifier
     *
     * @return string
     */
    public function getObjectIdentifier()
    {
        return $this->objectIdentifier;
    }

    /**
     * Sets the objectIdentifier
     *
     * @param string $objectIdentifier
     * @return void
     */
    public function setObjectIdentifier($objectIdentifier)
    {
        $this->objectIdentifier = $objectIdentifier;
    }

    /**
     * Returns the reservedObjectIdentifier
     *
     * @return string
     */
    public function getReservedObjectIdentifier()
    {
        return $this->reservedObjectIdentifier;
    }

    /**
     * Sets the reservedObjectIdentifier
     *
     * @param string $reservedObjectIdentifier
     * @return void
     */
    public function setReservedObjectIdentifier($reservedObjectIdentifier)
    {
        $this->reservedObjectIdentifier = $reservedObjectIdentifier;
    }

    /**
     * Returns the state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Sets the state
     *
     * @param string $state
     * @return void
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * Returns the transferStatus
     *
     * @return string
     */
    public function getTransferStatus()
    {
        return $this->transferStatus;
    }

    /**
     * Sets the transferStatus
     *
     * @param string
     * @return void
     */
    public function setTransferStatus($transferStatus)
    {
        $this->transferStatus = $transferStatus;
    }

    /**
     * Returns the transferDate
     *
     * @return integer
     */
    public function getTransferDate()
    {
        return $this->transferDate;
    }

    /**
     * Sets the transferDate
     *
     * @param integer $transferDate
     * @return void
     */
    public function setTransferDate($transferDate)
    {
        $this->transferDate = $transferDate;
    }

    /**
     * Returns the transferErrorCode
     *
     * @var integer
     */
    public function getTransferErrorCode()
    {
        return $this->transferErrorCode;
    }

    /**
     * Sets the transferErrorCode
     *
     * @param integer $transferErrorCode
     * @return void
     */
    public function setTransferErrorCode($transferErrorCode)
    {
        $this->transferErrorCode = $transferErrorCode;
    }

    /**
     * Returns the transferResponse
     *
     * @var string
     */
    public function getTransferResponse()
    {
        return $this->transferResponse;
    }

    /**
     * Sets the transferResponse
     *
     * @param string $transferResponse
     * @return void
     */
    public function setTransferResponse($transferResponse)
    {
        $this->transferResponse = $transferResponse;
    }

    /**
     * Returns the transferHttpStatus
     *
     * @var integer
     */
    public function getTransferHttpStatus()
    {
        return $this->transferHttpStatus;
    }

    /**
     * Sets the transferHttpStatus
     *
     * @param integer $transferHttpStatus
     * @return void
     */
    public function setTransferHttpStatus($transferHttpStatus)
    {
        $this->transferHttpStatus = $transferHttpStatus;
    }

    /**
     * Adds a File
     *
     * @param \EWW\Dpf\Domain\Model\File $file
     * @return void
     */
    public function addFile(\EWW\Dpf\Domain\Model\File $file)
    {
        $this->file->attach($file);
    }

    /**
     * Removes a File
     *
     * @param \EWW\Dpf\Domain\Model\File $fileToRemove The File to be removed
     * @return void
     */
    public function removeFile(\EWW\Dpf\Domain\Model\File $fileToRemove)
    {
        $this->file->detach($fileToRemove);
    }

    /**
     * Returns the file
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\File> $file
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Sets the file
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\File> $file
     * @return void
     */
    public function setFile(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $file)
    {
        $this->file = $file;
    }

    /**
     * Get File Data
     *
     * @return array
     */
    public function getFileData()
    {

        $fileId = new \EWW\Dpf\Services\Transfer\FileId($this);

        $files = array();

        if (is_a($this->getFile(), '\TYPO3\CMS\Extbase\Persistence\ObjectStorage')) {
            foreach ($this->getFile() as $file) {

                $tmpFile = array(
                    'path'      => $file->getLink(),
                    'type'      => $file->getContentType(),
                    'title'     => (($file->getLabel()) ? $file->getLabel() : $file->getTitle()),
                    'download'  => $file->getDownload(),
                    'archive'   => $file->getArchive(),
                    'use'       => '',
                    'id'        => null,
                    'hasFLocat' => ($file->getStatus() == \Eww\Dpf\Domain\Model\File::STATUS_ADDED ||
                                    $file->getStatus() == \Eww\Dpf\Domain\Model\File::STATUS_CHANGED),
                );

                $grpUSE = ($file->getDownload()) ? 'download' : 'original';

                if ($file->getStatus() == \Eww\Dpf\Domain\Model\File::STATUS_DELETED) {
                    $dataStreamIdentifier = $file->getDatastreamIdentifier();
                    if (!empty($dataStreamIdentifier)) {
                        $tmpFile['id']                   = $file->getDatastreamIdentifier();
                        $tmpFile['use']                  = 'DELETE';
                        $files[$grpUSE][$file->getUid()] = $tmpFile;
                    }
                } else {
                    $tmpFile['id']                   = $fileId->getId($file);
                    $tmpFile['use']                  = ($file->getArchive()) ? 'ARCHIVE' : '';
                    $files[$grpUSE][$file->getUid()] = $tmpFile;
                }

            }
        }

        return $files;

    }

    /**
     * Get Current File Data
     *
     * @return array
     */
    public function getCurrentFileData()
    {

        $fileId = new \EWW\Dpf\Services\Transfer\FileId($this);

        $files = array();

        if (is_a($this->getFile(), '\TYPO3\CMS\Extbase\Persistence\ObjectStorage')) {
            foreach ($this->getFile() as $file) {

                $tmpFile = array(
                    'path'      => $file->getLink(),
                    'type'      => $file->getContentType(),
                    'title'     => (($file->getLabel()) ? $file->getLabel() : $file->getTitle()),
                    'download'  => $file->getDownload(),
                    'archive'   => $file->getArchive(),
                    'use'       => '',
                    'id'        => null,
                    'hasFLocat' => ($file->getStatus() == \Eww\Dpf\Domain\Model\File::STATUS_ADDED ||
                                    $file->getStatus() == \Eww\Dpf\Domain\Model\File::STATUS_CHANGED),
                );

                $grpUSE = ($file->getDownload()) ? 'download' : 'original';

                if ($file->getStatus() == \Eww\Dpf\Domain\Model\File::STATUS_DELETED) {
                    $dataStreamIdentifier = $file->getDatastreamIdentifier();
                    if (!empty($dataStreamIdentifier)) {
                        $tmpFile['id']                   = $file->getDatastreamIdentifier();
                        $tmpFile['use']                  = 'DELETE';
                        $files[$grpUSE][$file->getUid()] = $tmpFile;
                    }
                } else {
                    $tmpFile['id']                   = $fileId->getId($file);
                    $tmpFile['use']                  = ($file->getArchive()) ? 'ARCHIVE' : '';
                    $files[$grpUSE][$file->getUid()] = $tmpFile;
                }

            }
        }

        return $files;

    }

    /**
     * Returns the changed
     *
     * @return boolean $changed
     */
    public function getChanged()
    {
        return $this->changed;
    }

    /**
     * Sets the changed
     *
     * @param boolean $changed
     * @return void
     */
    public function setChanged($changed)
    {
        $this->changed = $changed;
    }

    /**
     * Returns the valid
     *
     * @return boolean $valid
     */
    public function getValid()
    {
        return $this->valid;
    }

    /**
     * Sets the valid
     *
     * @param boolean $valid
     * @return void
     */
    public function setValid($valid)
    {
        $this->valid = $valid;
    }

    /**
     * Gets the Issue Date
     *
     * @return string
     */
    public function getDateIssued()
    {
        if (empty($this->dateIssued)) {
            return '';
        }
        return $this->dateIssued;
    }

    /**
     * Sets the Issue Date
     *
     * @param string $dateIssued
     * @return void
     */
    public function setDateIssued($dateIssued)
    {
        $this->dateIssued = $dateIssued;
    }

    /**
     *
     *
     * @return boolean
     */
    public function isDeleteAllowed()
    {
        return ($this->state == self::OBJECT_STATE_INACTIVE ||
            $this->state == self::OBJECT_STATE_ACTIVE) &&
        !empty($this->objectIdentifier);
    }


    public function isSaveAllowed()
    {
        return !($this->getState() != \EWW\Dpf\Domain\Model\Document::OBJECT_STATE_ACTIVE &&
            $this->getState() != \EWW\Dpf\Domain\Model\Document::OBJECT_STATE_INACTIVE &&
            $this->getState() != \EWW\Dpf\Domain\Model\Document::OBJECT_STATE_NEW);
    }


    /**
     *
     *
     * @return boolean
     */
    public function isActive()
    {
        return $this->state == self::OBJECT_STATE_ACTIVE ||
        $this->state == self::OBJECT_STATE_NEW ||
            ($this->state != self::OBJECT_STATE_INACTIVE &&
            $this->state != self::OBJECT_STATE_DELETED &&
            $this->state != self::OBJECT_STATE_LOCALLY_DELETED);
    }

    /**
     *
     *
     * @return boolean
     */
    public function isActivationChangeAllowed()
    {
        return $this->state == self::OBJECT_STATE_INACTIVE ||
        $this->state == self::OBJECT_STATE_ACTIVE;
    }

    /**
     *
     *
     * @return boolean
     */
    public function isDeleteRemote()
    {
        return $this->state == self::OBJECT_STATE_LOCALLY_DELETED;
    }

    /**
     *
     *
     * @return boolean
     */
    public function isRestoreRemote()
    {
        return $this->state == self::OBJECT_STATE_DELETED;
    }

    /**
     *
     *
     * @return boolean
     */
    public function isActivateRemote()
    {
        return $this->state == self::OBJECT_STATE_INACTIVE;
    }

    /**
     *
     *
     * @return boolean
     */
    public function isInactivateRemote()
    {
        return $this->state == self::OBJECT_STATE_ACTIVE;
    }

    /**
     *
     *
     * @return boolean
     */
    public function isIngestRemote()
    {
        return $this->state == self::OBJECT_STATE_NEW &&
        empty($this->objectIdentifier);
    }

    /**
     *
     *
     * @return boolean
     */
    public function isUpdateRemote()
    {
        return ($this->state == self::OBJECT_STATE_ACTIVE ||
            $this->state == self::OBJECT_STATE_INACTIVE) &&
        !empty($this->objectIdentifier);
    }

    /**
     *
     *
     * @return boolean
     */
    public function getIsNew()
    {
        return empty($this->objectIdentifier);
    }


    /**
     * Returns the process number
     *
     * @return string
     */
    public function getProcessNumber()
    {
        return $this->processNumber;
    }

    /**
     * Sets the process number
     *
     * @param string $processNumber
     * @return void
     */
    public function setProcessNumber($processNumber)
    {
        $this->processNumber = trim($processNumber);
    }

    /**
     * Returns the metadata
     *
     * @return array $metadata
     */
    public function getMetadata()
    {
        return unserialize($this->metadata);
    }

    /**
     * Sets the metadata
     *
     * @param array $metadata
     * @return void
     */
    public function setMetadata($metadata)
    {
        $this->metadata = serialize($metadata);
    }


    /**
     * Gets the main urn
     *
     * @return string
     */
    public function getUrn()
    {
        $urn = "";

        $objectIdentifier = $this->getObjectIdentifier();

        if (empty($objectIdentifier)) {
            $objectIdentifier = $this->getReservedObjectIdentifier();
        }

        if (!empty($objectIdentifier)) {
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\Object\\ObjectManager');
            $urnService = $objectManager->get('EWW\\Dpf\\Services\\Identifier\\Urn');
            $urn  = $urnService->getUrn($objectIdentifier);
        }

        return $urn;
    }


    /**
     * Sets the new files
     *
     * @param array $newFiles
     * @return void
     */
    public function setNewFiles($newFiles)
    {
        $this->newFiles = $newFiles;
    }

    /**
     * Gets the new files
     *
     * @return array
     */
    public function getNewFiles()
    {
        return $this->newFiles;
    }

    /**
     * Sets the deleted files
     *
     * @param array $deletedFiles
     * @return void
     */
    public function setDeletedFiles($deletedFiles)
    {
        $this->seletedFiles = $deletedFiles;
    }

    /**
     * Gets the deleted files
     *
     * @return array
     */
    public function getDeletedFiles()
    {
        return $this->deletedFiles;
    }

}
