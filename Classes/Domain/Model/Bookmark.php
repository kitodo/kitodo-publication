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
 * Class Bookmark
 *
 *
 * @package EWW\Dpf\Domain\Model
 */
class Bookmark extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * documentIdentifier : A document uid or a qucosa object identifier.
     *
     * @var string
     */
    protected $documentIdentifier = '';

    /**
     * ownerUid : Uid of the editor frontend user.
     *
     * @var integer
     */
    protected $ownerUid = 0;

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
     * Gets the owner uid
     *
     * @return int
     */
    public function getOwnerUid()
    {
        return $this->ownerUid;
    }

    /**
     * Sets the editor uid
     *
     * @param int $ownerUid
     */
    public function setOwnerUid($ownerUid)
    {
        $this->ownerUid = $ownerUid;
    }

}