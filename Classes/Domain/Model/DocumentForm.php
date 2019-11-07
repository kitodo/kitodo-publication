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

class DocumentForm extends AbstractFormElement
{

    /**
     *
     * @var integer
     */
    protected $documentUid;

    /**
     *
     * @var boolean
     */
    protected $virtual;

    /**
     *
     * @var string
     */
    protected $qucosaId;

    /**
     *
     * @var \EWW\Dpf\Domain\Model\File
     */
    protected $primaryFile;

    /**
     *
     * @var array
     */
    protected $secondaryFiles;

    /**
     *
     * @var array
     */
    protected $deletedFiles;

    /**
     *
     * @var array
     */
    protected $newFiles;

    /**
     *
     * @var string
     */
    protected $objectState;

    /**
     *
     * @var boolean
     */
    protected $valid = false;

    /**
     *
     * @var string
     */
    protected $processNumber;

    /**
     * @var bool
     */
    protected $temporary;

    /**
     * @var string
     */
    protected $comment = '';

    /**
     *
     * @return integer
     */
    public function getDocumentUid()
    {
        return $this->documentUid;
    }

    /**
     *
     * @param integer $documentUid
     */
    public function setDocumentUid($documentUid)
    {
        $this->documentUid = $documentUid;
    }

    /**
     *
     * @return boolean
     */
    public function getVirtual()
    {
        return $this->virtual;
    }

    /**
     *
     * @param boolean $virtual
     */
    public function setVirtual($virtual)
    {
        $this->virtual = $virtual;
    }

    /**
     *
     * @return string
     */
    public function getQucosaId()
    {
        return $this->qucosaId;
    }

    /**
     *
     * @param string $qucosaId
     */
    public function setQucosaId($qucosaId)
    {
        $this->qucosaId = $qucosaId;
    }

    /**
     *
     * @param type \EWW\Dpf\Domain\Model\File $primaryFile
     */
    public function setPrimaryFile($primaryFile)
    {
        $this->primaryFile = $primaryFile;
    }

    /**
     *
     * @return \EWW\Dpf\Domain\Model\File
     */
    public function getPrimaryFile()
    {
        return $this->primaryFile;
    }

    public function setSecondaryFiles($secondaryFiles)
    {
        $this->secondaryFiles = $secondaryFiles;
    }

    public function getSecondaryFiles()
    {
        return $this->secondaryFiles;
    }

    public function getDeletedFiles()
    {
        return $this->deletedFiles;
    }

    public function setDeletedFiles($deletedFiles)
    {
        $this->deletedFiles = $deletedFiles;
    }

    public function getNewFiles()
    {
        return $this->newFiles;
    }

    public function setNewFiles($newFiles)
    {
        $this->newFiles = $newFiles;
    }

    public function getValid()
    {
        return $this->valid;
    }

    public function setValid($valid)
    {
        $this->valid = $valid;
    }

    public function getNewFileNames()
    {
        $fileNames = array();
        foreach ($this->getNewFiles() as $file) {
            $fileNames[] = $file->getTitle();
        }
        return $fileNames;
    }

    /**
     * Sets the process number
     *
     * @return string
     */
    public function getProcessNumber()
    {
        return $this->processNumber;
    }

    /**
     * Gets the process number
     *
     * @param string $processNumber
     */
    public function setProcessNumber($processNumber)
    {
        $this->processNumber = $processNumber;
    }

    /**
     * Getss the temporary
     *
     * @return bool
     */
    public function getTemporary()
    {
        return $this->temporary;
    }

    /**
     * Sets the temporary
     * @param bool $temporary
     */
    public function setTemporary($temporary)
    {
        $this->temporary = $temporary;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }



}
