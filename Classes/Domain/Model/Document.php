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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Helper\InternalFormat;

/**
 * Document
 */
class Document extends AbstractEntity
{
    // xml data size ist limited to 64 KB
    const XML_DATA_SIZE_LIMIT = 64 * 1024;

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
    protected $objectIdentifier = '';

    /**
     * reservedObjectIdentifier
     *
     * @var string
     * @deprecated
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
    protected $processNumber = '';

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
     * creation date
     *
     * @var string
     */
    protected $creationDate = "";

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
     * crdate
     *
     * @var integer
     */
    protected $crdate;

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
     * newlyAssignedFobIdentifiers
     *
     * @var array
     */
    protected $newlyAssignedFobIdentifiers = [];

    /**
     * @var bool
     */
    protected $stateChange = false;

    /**
     * file
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\File>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
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
        $this->initCreationDate();
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
        $this->title = $title ?? '';
        //htmlspecialchars_decode($title,ENT_QUOTES);
    }

    /**
     * Returns the authors
     *
     * @return array $authors
     */
    public function getAuthors()
    {
        $authors = @unserialize($this->authors);
        if (is_array($authors)) {
            return $authors;
        } else {
            return [];
        }
    }

    /**
     * Sets the authors
     *
     * @param array $authors
     * @return void
     */
    public function setAuthors($authors)
    {
        $this->authors = serialize($authors);
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
     * Returns the XML taking into account any existing embargo
     *
     * @return string
     */
    public function publicXml(): string
    {
        $internalFormat = new InternalFormat($this->getXmlData());
        $currentDate = new \DateTime('now');
        if ($currentDate < $this->getEmbargoDate()) {
            $internalFormat->removeAllFiles();
        } else {
            $internalFormat->completeFileData($this->getFile());
        }

        return $internalFormat->getXml();
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
        // Due to uniqe key uc_object_identifier, which should ignore empty object identifiers.
        $this->objectIdentifier = empty($objectIdentifier)? null : $objectIdentifier;
    }

    /**
     * Returns the reservedObjectIdentifier
     *
     * @return string
     * @deprecated
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
     * @deprecated
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
     * @param string $fileIdentifier
     * @return \EWW\Dpf\Domain\Model\File|null
     */
    public function getFileByFileIdentifier(string $fileIdentifier): ?\EWW\Dpf\Domain\Model\File
    {
        foreach ($this->file as $file) {
            if ($file->getFileIdentifier() == $fileIdentifier) {
                return $file;
            }
        }
        return null;
    }

    /**
     * @return \EWW\Dpf\Domain\Model\File|null
     */
    public function getPrimaryFile(): ?\EWW\Dpf\Domain\Model\File
    {
        /** @var File $file */
        foreach ($this->file as $file) {
            if ($file->isPrimaryFile()) {
                return $file;
            }
        }
        return null;
    }

    /**
     * Has files
     */
    public function hasFiles()
    {
        if (is_a($this->getFile(), '\TYPO3\CMS\Extbase\Persistence\ObjectStorage')) {
            foreach ($this->getFile() as $file) {
                /** @var File $file */
                if (!$file->isFileGroupDeleted()) {
                    return true;
                }
            }
        }

        return false;
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
        $this->changed = boolval($changed);
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
        $this->valid = boolval($valid);
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
            $internalFormat = new InternalFormat($this->getXmlData(), $this->getPid());
            return $internalFormat->getSubmitterName();
        } catch (\Exception $exception) {
            return "";
        }
    }

    /**
     * Gets the primary urn of the document
     *
     * @return string
     */
    public function getPrimaryUrn()
    {
        $internalFormat = new InternalFormat($this->getXmlData(), $this->getPid());
        return $internalFormat->getPrimaryUrn();
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
        $this->stateChange = $this->state != $state;
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
        $this->temporary = boolval($temporary);
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
     * @return integer
     */
    public function getPid()
    {
        return $this->pid;
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
        $this->suggestion = boolval($suggestion);
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
        $internalFormat = new InternalFormat($this->getXmlData(), $this->getPid());
        return $internalFormat->getNotes();
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
        $internalFormat = new InternalFormat($this->getXmlData(), $this->getPid());
        $year =  $internalFormat->getPublishingYear();
        return $year? $year : "";
    }

    /**
     * Gets the source information out of the mods-xml data.
     *
     * @return string|null
     */
    public function getSourceDetails()
    {
        $internalFormat = new InternalFormat($this->getXmlData(), $this->getPid());
        $data = $internalFormat->getSourceDetails();
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

    /**
     * @return array
     */
    public function getNewlyAssignedFobIdentifiers(): array
    {
        return $this->newlyAssignedFobIdentifiers;
    }

    /**
     * @param array newlyAssignedFobIdentifiers
     */
    public function setNewlyAssignedFobIdentifiers(array $newlyAssignedFobIdentifiers): void
    {
        $this->newlyAssignedFobIdentifiers = $newlyAssignedFobIdentifiers;
    }

    /**
     * @return array
     */
    public function getPreviouslyAssignedFobIdentifiers()
    {
        return array_diff(
            $this->getAssignedFobIdentifiers(), $this->getNewlyAssignedFobIdentifiers()
        );
    }

    /**
     * @return array
     */
    public function getAssignedFobIdentifiers(): array
    {
        $internalFormat = new InternalFormat($this->getXmlData(), $this->getPid());
        return $internalFormat->getPersonFisIdentifiers();
    }

    /**
     * @return bool
     */
    public function isStateChange(): bool
    {
        return $this->stateChange;
    }

    /**
     * @return mixed
     */
    public function getDepositLicense()
    {
        $internalFormat = new InternalFormat($this->getXmlData(), $this->getPid());
        $data = $internalFormat->getDepositLicense();
        return $data;
    }

    /**
     * @return string
     */
    public function getCreationDate(): string
    {
        if (
            $this->getRemoteState() == DocumentWorkflow::REMOTE_STATE_NONE
            && empty($this->creationDate)
        ) {
            $date = new \DateTime();
            $date->setTimestamp($this->crdate);
            return $date->format(\DateTimeInterface::RFC3339_EXTENDED);
        }

        return $this->creationDate;
    }

    /**
     * @param string $creationDate
     */
    public function setCreationDate(string $creationDate): void
    {
        $this->creationDate = $creationDate;
    }

    /**
     * Initializes the creation date with the current date.
     */
    public function initCreationDate(): void
    {
        $date = new \DateTime();
        $this->setCreationDate($date->format(\DateTimeInterface::RFC3339_EXTENDED));
    }

    /**
     * @return bool
     */
    public function isClientChangeable()
    {
        return (
            in_array(
                $this->getState(),
                [
                    DocumentWorkflow::STATE_REGISTERED_NONE,
                    DocumentWorkflow::STATE_IN_PROGRESS_NONE,
                    DocumentWorkflow::STATE_POSTPONED_NONE,
                    DocumentWorkflow::STATE_DISCARDED_NONE
                ]
            ) && !$this->stateChange
        );
    }

    /**
     * @return bool
     */
    public function isFullTextPublication()
    {
        /** @var File $file */
        foreach ($this->getFile() as $file) {
            if ($file->getDownload()) {
                return true;
            }
        }

        return false;
    }
}
