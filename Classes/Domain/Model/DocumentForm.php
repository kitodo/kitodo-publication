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
    protected $primaryFileMandatory;

    /**
     *
     * @var string
     */
    protected $fedoraPid;

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
    public function getPrimaryFileMandatory()
    {
        return $this->primaryFileMandatory;
    }

    /**
     *
     * @param boolean $primaryFileMandatory
     */
    public function setPrimaryFileMandatory($primaryFileMandatory)
    {
        $this->primaryFileMandatory = boolval($primaryFileMandatory);
    }

    /**
     *
     * @return string
     */
    public function getFedoraPid()
    {
        return $this->fedoraPid;
    }

    /**
     *
     * @param string $fedoraPid
     */
    public function setFedoraPid($fedoraPid)
    {
        $this->fedoraPid = $fedoraPid;
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

    /**
     * @return bool
     */
    public function getValid()
    {
        return $this->valid;
    }

    /**
     * @param bool $valid
     */
    public function setValid($valid)
    {
        $this->valid = boolval($valid);
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
     * Returns if a document is a temporary document.
     *
     * @return bool
     */
    public function isTemporary()
    {
        return $this->temporary;
    }

    /**
     * Sets if a document is a temporary document or not.
     * @param bool $temporary
     */
    public function setTemporary($temporary)
    {
        $this->temporary = boolval($temporary);
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
