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
     * documentIdentifier : A document uid or a fedoroPid object identifier.
     *
     * @var string
     */
    protected $documentIdentifier = '';

    /**
     * feUserUid : Uid of the frontend user.
     *
     * @var integer
     */
    protected $feUserUid = 0;

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
     * Gets the feuser uid
     *
     * @return int
     */
    public function getFeUserUid()
    {
        return $this->feUserUid;
    }

    /**
     * Sets the feuser uid
     *
     * @param int $feUserUid
     */
    public function setFeUserUid($feUserUid)
    {
        $this->feUserUid = $feUserUid;
    }

}