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
 * MetadataPage
 */
class MetadataPage extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * displayName
     *
     * @var string
     */
    protected $displayName = '';

    /**
     * pageNumber
     *
     * @var integer
     */
    protected $pageNumber = 0;

    /**
     * metadataGroup
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataGroup>
     */
    protected $metadataGroup = null;

    /**
     * backendOnly
     *
     * @var boolean
     */
    protected $backendOnly = false;

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
        $this->metadataGroup = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Returns the name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name
     *
     * @param string $name
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the displayName
     *
     * @return string $displayName
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * Sets the displayName
     *
     * @param string $displayName
     * @return void
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;
    }

    /**
     * Returns the pageNumber
     *
     * @return integer $pageNumber
     */
    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    /**
     * Sets the pageNumber
     *
     * @param integer $pageNumber
     * @return void
     */
    public function setPageNumber($pageNumber)
    {
        $this->pageNumber = $pageNumber;
    }

    /**
     * Adds a MetadataGroup
     *
     * @param \EWW\Dpf\Domain\Model\MetadataGroup $metadataGroup
     * @return void
     */
    public function addMetadataGroup(\EWW\Dpf\Domain\Model\MetadataGroup $metadataGroup)
    {
        $this->metadataGroup->attach($metadataGroup);
    }

    /**
     * Removes a MetadataGroup
     *
     * @param \EWW\Dpf\Domain\Model\MetadataGroup $metadataGroupToRemove The MetadataGroup to be removed
     * @return void
     */
    public function removeMetadataGroup(\EWW\Dpf\Domain\Model\MetadataGroup $metadataGroupToRemove)
    {
        $this->metadataGroup->detach($metadataGroupToRemove);
    }

    /**
     * Returns the metadataGroup
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataGroup> $metadataGroup
     */
    public function getMetadataGroup()
    {
        return $this->metadataGroup;
    }

    /**
     * Sets the metadataGroup
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataGroup> $metadataGroup
     * @return void
     */
    public function setMetadataGroup(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $metadataGroup)
    {
        $this->metadataGroup = $metadataGroup;
    }

    /**
     * Alias for function getMetadataGroup()
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataGroup> $metadataGroup
     */
    public function getChildren()
    {
        return $this->getMetadataGroup();
    }

    /**
     * Returns the backendOnly
     *
     * @return boolean $backendOnly
     */
    public function getBackendOnly()
    {
        return $this->backendOnly;
    }

    /**
     * Sets the backendOnly
     *
     * @param boolean $backendOnly
     * @return void
     */
    public function setBackendOnly($backendOnly)
    {
        $this->backendOnly = $backendOnly;
    }

}
