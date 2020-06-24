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

use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Helper\Mods;

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
    protected $objectIdentifier = null;

    /**
     * reservedObjectIdentifier
     *
     * @var string
     */
    protected $reservedObjectIdentifier;

    /**
     * transferStatus
     *
     * @var string
     */
    protected $transferStatus;

    /**
     *  transferDate
     *
     * @var integer
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
     * @var bool $suggestion
     */
    protected $suggestion = false;

    /**
     * creator
     *
     * @var int
     */
    protected $creator = 0;

    /**
     * state
     *
     * @var string
     */
    protected $state = DocumentWorkflow::STATE_NONE_NONE;

    /**
     * temporary
     *
     * @var boolean
     */
    protected $temporary = FALSE;

    /**
     * remoteLastModDate
     *
     * @var string
     */
    protected $remoteLastModDate = '';

    /**
     * tstamp
     *
     * @var integer
     */
    protected $tstamp;

    /**
     * @var string
     */
    protected $linkedUid = '';

    /**
     * @var string
     */
    protected $comment = '';

    /**
     * date
     *
     * @var \DateTime
     */
    protected $embargoDate = null;

    /**
     * file
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\File>
     * @cascade remove
     */
    protected $file = null;

    const TRANSFER_ERROR = "ERROR";
    const TRANSFER_QUEUED = "QUEUED";
    const TRANSFER_SENT = "SENT";

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
        $authors = implode("; ", $authors);
        $this->authors = $authors;
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
        $slub = new \EWW\Dpf\Helper\Slub($this->getSlubInfoData());
        $slub->setDocumentType($documentType->getName());
        $this->setSlubInfoData($slub->getSlubXml());
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
        // Due to uniqe key uc_object_identifier, which should ignore empty object identifiers.
        $this->objectIdentifier = empty($objectIdentifier)? null : $objectIdentifier;
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

                if (!$file->isFileGroupDeleted()) {

                    $tmpFile = array(
                        'path' => $file->getUrl(),
                        'type' => $file->getContentType(),
                        'title' => (($file->getLabel()) ? $file->getLabel() : $file->getTitle()),
                        'download' => $file->getDownload(),
                        'archive' => $file->getArchive(),
                        'use' => '',
                        'id' => null,
                        'hasFLocat' => ($file->getStatus() == \EWW\Dpf\Domain\Model\File::STATUS_ADDED ||
                            $file->getStatus() == \EWW\Dpf\Domain\Model\File::STATUS_CHANGED),
                    );

                    $grpUSE = ($file->getDownload()) ? 'download' : 'original';

                    if ($file->getStatus() == \EWW\Dpf\Domain\Model\File::STATUS_DELETED) {
                        $dataStreamIdentifier = $file->getDatastreamIdentifier();
                        if (!empty($dataStreamIdentifier)) {
                            $tmpFile['id'] = $file->getDatastreamIdentifier();
                            $tmpFile['use'] = 'DELETE';
                            $files[$grpUSE][$file->getUid()] = $tmpFile;
                        }
                    } else {
                        $tmpFile['id'] = $fileId->getId($file);
                        $tmpFile['use'] = ($file->getArchive()) ? 'ARCHIVE' : '';
                        $files[$grpUSE][$file->getUid()] = $tmpFile;
                    }
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
                    'path' => $file->getUrl(),
                    'type' => $file->getContentType(),
                    'title' => (($file->getLabel()) ? $file->getLabel() : $file->getTitle()),
                    'download' => $file->getDownload(),
                    'archive' => $file->getArchive(),
                    'use' => '',
                    'id' => null,
                    'hasFLocat' => ($file->getStatus() == \EWW\Dpf\Domain\Model\File::STATUS_ADDED ||
                        $file->getStatus() == \EWW\Dpf\Domain\Model\File::STATUS_CHANGED),
                );

                $grpUSE = ($file->getDownload()) ? 'download' : 'original';

                if ($file->getStatus() == \EWW\Dpf\Domain\Model\File::STATUS_DELETED) {
                    $dataStreamIdentifier = $file->getDatastreamIdentifier();
                    if (!empty($dataStreamIdentifier)) {
                        $tmpFile['id'] = $file->getDatastreamIdentifier();
                        $tmpFile['use'] = 'DELETE';
                        $files[$grpUSE][$file->getUid()] = $tmpFile;
                    }
                } else {
                    $tmpFile['id'] = $fileId->getId($file);
                    $tmpFile['use'] = ($file->getArchive()) ? 'ARCHIVE' : '';
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
        return empty($this->dateIssued) ? '' : $this->dateIssued;
    }

    /**
     * Sets the Issue Date
     *
     * @param string $dateIssued
     * @return void
     */
    public function setDateIssued($dateIssued)
    {
        $this->dateIssued = empty($dateIssued) ? '' : $dateIssued;
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
     * Gets the submitter name of the document
     *
     * @return string
     */
    public function getSubmitterName()
    {
        try {
            $slub = new \EWW\Dpf\Helper\Slub($this->getSlubInfoData());
            return $slub->getSubmitterName();
        } catch (\Exception $exception) {
            return "";
        }
    }

    /**
     * Gets the qucosa urn of the document
     *
     * @return string
     */
    public function getQucosaUrn()
    {
        $mods = new \EWW\Dpf\Helper\Mods($this->getXmlData());
        return $mods->getQucosaUrn();
    }

    /**
     * Returns the creator feuser uid
     *
     * @return int
     */
    public function getCreator()
    {
        return $this->creator? $this->creator : 0;
    }

    /**
     * Sets the creator feuser uid
     *
     * @param int $creator
     * @return void
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function getRemoteState()
    {
        $state = explode(':', $this->state);
        if (is_array($state) && array_key_exists(1, $state)) {
            return $state[1];
        }
        return DocumentWorkflow::REMOTE_STATE_NONE;
    }

    public function getLocalState() {
        $state = explode(':', $this->state);
        if (is_array($state) && array_key_exists(0, $state)) {
            return $state[0];
        }
        return DocumentWorkflow::LOCAL_STATE_NONE;
    }

    /**
     * Returns if a document is a temporary document.
     *
     * @return boolean $temporary
     */
    public function isTemporary() {
        return $this->temporary;
    }

    /**
     * Sets if a document is a temporary document or not.
     *
     * @param boolean $temporary
     * @return void
     */
    public function setTemporary($temporary) {
        $this->temporary = $temporary;
    }

    /**
     * @return string
     */
    public function getRemoteLastModDate()
    {
        return $this->remoteLastModDate;
    }

    /**
     * @param string $remoteLastModDate
     * @return void
     */
    public function setRemoteLastModDate($remoteLastModDate)
    {
        $this->remoteLastModDate = $remoteLastModDate;
    }

    /**
     * @return integer
     */
    public function getTstamp()
    {
        return $this->tstamp;
    }

    /**
     * @return bool
     */
    public function isSuggestion(): bool
    {
        return $this->suggestion;
    }

    /**
     * @param bool $suggestion
     */
    public function setSuggestion(bool $suggestion)
    {
        $this->suggestion = $suggestion;
    }

    /**
     * @return string
     */
    public function getLinkedUid(): string
    {
        return $this->linkedUid;
    }

    /**
     * @param string $linkedUid
     */
    public function setLinkedUid(string $linkedUid)
    {
        $this->linkedUid = $linkedUid;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment(string $comment)
    {
        $this->comment = $comment;
    }

    /**
     * Copies the data of the given document object into the current document object.
     *
     * @param Document $documentToCopy
     * @return $this
     * @throws \TYPO3\CMS\Extbase\Reflection\Exception\PropertyNotAccessibleException
     */
    public function copy(Document $documentToCopy) {
        $availableProperties = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getGettablePropertyNames($documentToCopy);
        $newDocument = $this;

        foreach ($availableProperties as $propertyName) {
            if (\TYPO3\CMS\Extbase\Reflection\ObjectAccess::isPropertySettable($newDocument, $propertyName)
                && !in_array($propertyName, array('uid','pid', 'file', 'comment', 'linkedUid', 'suggestion', 'creator'))) {

                $propertyValue = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($documentToCopy, $propertyName);
                \TYPO3\CMS\Extbase\Reflection\ObjectAccess::setProperty($newDocument, $propertyName, $propertyValue);
            }
        }

        return $this;
    }

    public function getNotes() {
        $slub = new \EWW\Dpf\Helper\Slub($this->getSlubInfoData());
        return $slub->getNotes();
    }

    /**
     * Gets the document Identifier
     *
     * @return string|int
     */
    public function getDocumentIdentifier()
    {
        return $this->getObjectIdentifier()? $this->getObjectIdentifier() : $this->getUid();
    }

    /**
     * Returns if a document is a working copy of a published document.
     *
     * @return bool
     */
    public function isWorkingCopy()
    {
        return $this->getObjectIdentifier() && !$this->isTemporary() && !$this->isSuggestion();
    }


    /**
     * Returns if a document is a temporary copy of a published document.
     *
     * @return bool
     */
    public function isTemporaryCopy()
    {
        return $this->getObjectIdentifier() && $this->isTemporary() && !$this->isSuggestion();
    }


    /**
     * Gets the publication year out of the mods-xml data.
     *
     * @return string|null
     */
    public function getPublicationYear()
    {
        $mods = new Mods($this->getXmlData());
        $year =  $mods->getPublishingYear();
        return $year? $year : "";
    }

    /**
     * Gets the main title out of the mods-xml data.
     *
     * @return string|null
     */
    public function getMainTitle()
    {
        $mods = new Mods($this->getXmlData());
        $title = $mods->getTitle();
        return $title? $title : "";
    }


    /**
     * Gets the source information out of the mods-xml data.
     *
     * @return string|null
     */
    public function getSourceDetails()
    {
        $mods = new Mods($this->getXmlData());
        $data = $mods->getSourceDetails();
        return $data;
    }

    /**
     * @return \DateTime|null
     */
    public function getEmbargoDate(): ?\DateTime
    {
        return $this->embargoDate;
    }

    /**
     * @param \DateTime|null $embargoDate
     */
    public function setEmbargoDate(?\DateTime $embargoDate)
    {
        $this->embargoDate = $embargoDate;
    }

}
