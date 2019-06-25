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
 * File
 */
class File extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * title
     *
     * @var string
     */
    protected $title = '';

    /**
     * label
     *
     * @var string
     */
    protected $label = '';

    /**
     * download
     *
     * @var boolean
     */
    protected $download;

    /**
     * archive
     *
     * @var boolean
     */
    protected $archive;

    /**
     * fileGroupDeleted
     *
     * @var boolean
     */
    protected $fileGroupDeleted;

    /**
     * contentType
     *
     * @var string
     */
    protected $contentType = '';

    /**
     * link
     *
     * @var string
     */
    protected $link = '';

    /**
     * status
     *
     * @var string
     */
    protected $status;

    /**
     * primaryFile
     *
     * @var boolean
     */
    protected $primaryFile;

    /**
     * datastreamIdentifier
     *
     * @var string
     */
    protected $datastreamIdentifier;

    /**
     * document
     *
     * @var \EWW\Dpf\Domain\Model\Document
     */
    protected $document = null;

    const STATUS_REMOTE  = "remote";
    const STATUS_DELETED = "deleted";
    const STATUS_CHANGED = "changed";
    const STATUS_ADDED   = "added";

    const PRIMARY_DATASTREAM_IDENTIFIER = 'ATT-0';
    const DATASTREAM_IDENTIFIER_PREFIX  = 'ATT-';

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
    }

    /**
     * Returns the label
     *
     * @return string $label
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Sets the label
     *
     * @param string $label
     * @return void
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Returns the download
     *
     * @return boolean $download
     */
    public function getDownload()
    {
        return $this->download;
    }

    /**
     * Sets the download
     *
     * @param string $download
     * @return void
     */
    public function setDownload($download)
    {
        $this->download = $download;
    }

    /**
     * Returns the arcive
     *
     * @return boolean $archive
     */
    public function getArchive()
    {
        return $this->archive;
    }

    /**
     * Sets the archive
     *
     * @param boolean $archive
     * @return void
     */
    public function setArchive($archive)
    {
        $this->archive = $archive;
    }

    /**
     * Returns the fileGroupDeleted
     *
     * @return boolean $fileGroupDeleted
     */
    public function isFileGroupDeleted()
    {
        return $this->fileGroupDeleted;
    }

    /**
     * Sets the fileGroupDeleted
     *
     * @return boolean $fileGroupDeleted
     */
    public function setFileGroupDeleted($fileGroupDeleted)
    {
        $this->fileGroupDeleted = $fileGroupDeleted;
    }

    /**
     * Returns the contentType
     *
     * @return string $contentType
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Sets the contentType
     *
     * @param string $contentType
     * @return void
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * Returns the link
     *
     * @return string $link
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Sets the link
     *
     * @param string $link
     * @return void
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * Returns the status
     *
     * @return string $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets the status
     *
     * @param string $status
     * @return void
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Returns the document
     *
     * @return \EWW\Dpf\Domain\Model\Document $document
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * Sets the document
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function setDocument(\EWW\Dpf\Domain\Model\Document $document)
    {
        $this->document = $document;
    }

    /**
     * Returns the primaryFile
     *
     * @return boolean $primaryFile
     */
    public function getPrimaryFile()
    {
        return $this->primaryFile;
    }

    /**
     * Sets the primaryFile
     *
     * @param boolean $primaryFile
     * @return void
     */
    public function setPrimaryFile($primaryFile)
    {
        $this->primaryFile = $primaryFile;
    }

    /**
     * Returns the boolean state of primaryFile
     *
     * @return boolean
     */
    public function isPrimaryFile()
    {
        return $this->primaryFile;
    }

    /**
     * Returns the datastreamIdentifier
     *
     * @return string $datastreamIdentifier
     */
    public function getDatastreamIdentifier()
    {
        return $this->datastreamIdentifier;
    }

    /**
     * Sets the datastreamIdentifier
     *
     * @param string $datastreamIdentifier
     * @return void
     */
    public function setDatastreamIdentifier($datastreamIdentifier)
    {
        $this->datastreamIdentifier = $datastreamIdentifier;
    }

}
