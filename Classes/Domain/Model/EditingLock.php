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
 * Class EditingLock
 *
 * Model for the document locking mechanism in case of a document being edited
 *
 * @package EWW\Dpf\Domain\Model
 */
class EditingLock extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * documentIdentifier : A document uid or a fedoraPid object identifier.
     *
     * @var string
     */
    protected $documentIdentifier = '';

    /**
     * editorUid : Uid of the editor frontend user.
     *
     * @var integer
     */
    protected $editorUid = 0;

    /**
     * Gets the document identifier
     *
     * @return int
     */
    public function getDocumentIdentifier()
    {
        return $this->documentIdentifier;
    }

    /**
     * Sets the document identifier
     *
     * @param int $documentIdentifier
     */
    public function setDocumentIdentifier($documentIdentifier)
    {
        $this->documentIdentifier = $documentIdentifier;
    }

    /**
     * Gets the editor uid
     *
     * @return int
     */
    public function getEditorUid()
    {
        return $this->editorUid;
    }

    /**
     * Sets the editor uid
     *
     * @param int $editorUid
     */
    public function setEditorUid($editorUid)
    {
        $this->editorUid = $editorUid;
    }

}