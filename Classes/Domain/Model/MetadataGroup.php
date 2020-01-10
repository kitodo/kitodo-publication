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
 * MetadataGroup
 */
class MetadataGroup extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity implements MetadataMandatoryInterface
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
     * mandatory
     *
     * @var string
     */
    protected $mandatory = '';

    /**
     * mapping
     *
     * @var string
     */
    protected $mapping = '';

    /**
     * mappingForReading
     *
     * @var string
     */
    protected $mappingForReading = '';

    /**
     * modsExtensionMapping
     *
     * @var string
     */
    protected $modsExtensionMapping = '';

    /**
     * modsExtensionReference
     *
     * @var string
     */
    protected $modsExtensionReference = '';

    /**
     * maxIteration
     *
     * @var integer
     */
    protected $maxIteration = 0;

    /**
     * metadataObject
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataObject>
     * @cascade remove
     */
    protected $metadataObject = null;

    /**
     * accessRestrictionRoles
     *
     * @var string
     */
    protected $accessRestrictionRoles = '';

    /**
     * infoText
     *
     * @var string
     */
    protected $infoText;

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
        $this->metadataObject = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
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
     * Returns the mandatory
     *
     * @return string $mandatory
     */
    public function getMandatory()
    {
        return $this->mandatory;
    }

    /**
     * Sets the mandatory
     *
     * @param string $mandatory
     * @return void
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = $mandatory;
    }

    /**
     * Returns the mapping
     *
     * @return string $mapping
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * Sets the mapping
     *
     * @param string $mapping
     * @return void
     */
    public function setMapping($mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * Returns the mappingForReading
     *
     * @return string $mappingForReading
     */
    public function getMappingForReading()
    {
        return $this->mappingForReading;
    }

    /**
     * Sets the mappingForReading
     *
     * @param string $mappingForReading
     * @return void
     */
    public function setMappingForReading($mappingForReading)
    {
        $this->mappingForReading = $mappingForReading;
    }

    /**
     * Checks if a mapping for reading is defined
     *
     * @return bool
     */
    public function hasMappingForReading()
    {
        $mapping = trim($this->mappingForReading);
        return !empty($mapping);
    }

    /**
     * Returns the relative mapping
     *
     * @string $mapping
     * @return string $relativeMapping
     */
    protected function relativeMapping($mapping)
    {
        $modsRegExp = "/^\/?(mods:mods|slub:info)/";
        $mapping    = preg_replace($modsRegExp, "", $mapping);
        return trim($mapping, " /");
    }

    /**
     * Returns the relative mapping for writing
     *
     * @return string $relativeMappingForWriting
     */
    public function getRelativeMapping()
    {
        return $this->relativeMapping($this->mapping);
    }

    /**
     * Returns the relative mapping for reading
     *
     * @return string $relativeMappingForReading
     */
    public function getRelativeMappingForReading()
    {
        return $this->relativeMapping($this->mappingForReading);
    }

    /**
     * Returns the absolute mapping for writing
     *
     * @return string $absoluteMappingForWriting
     */
    public function getAbsoluteMapping()
    {
        if ($this->isSlubInfo($this->getMapping())) {
            return "/slub:info/" . $this->getRelativeMapping();
        } else {
            return "/mods:mods/" . $this->getRelativeMapping();
        }
    }

    /**
     * Returns the absolute mapping for reading
     *
     * @return string $absoluteMappingForReading
     */
    public function getAbsoluteMappingForReading()
    {
        if ($this->isSlubInfo($this->getMappingForReading())) {
            return "/slub:info/" . $this->getRelativeMappingForReading();
        } else {
            return "/mods:mods/" . $this->getRelativeMappingForReading();
        }
    }

    /**
     * Checks if a mapping defines a slub:info node
     *
     * @param string $mapping
     * @return bool
     */
    public function isSlubInfo($mapping)
    {
        $modsRegExp = "/^\/?slub:info/";
        $match      = $mapping;
        if (preg_match($modsRegExp, $match)) {
            return true;
        }
        return false;
    }

    /**
     * Returns the modsExtensionMapping
     *
     * @return string $modsExtensionMapping
     */
    public function getModsExtensionMapping()
    {
        return $this->modsExtensionMapping;
    }

    /**
     * Sets the modsExtensionMapping
     *
     * @param string $modsExtensionMapping
     * @return void
     */
    public function setModsExtensionMapping($modsExtensionMapping)
    {
        $this->modsExtensionMapping = $modsExtensionMapping;
    }

    /**
     * Returns the relative mods extension mapping
     *
     * @return string $relativeModsExtensionMapping
     */
    public function getRelativeModsExtensionMapping()
    {
        $modsRegExp = "/^.*?mods:mods/i";
        $mapping    = preg_replace($modsRegExp, "", $this->modsExtensionMapping);
        return trim($mapping, " /");
    }

    /**
     * Returns the absolute mods extension mapping
     *
     * @return string $absoluteModsExtensionMapping
     */
    public function getAbsoluteModsExtensionMapping()
    {
        return "/mods:mods/" . $this->getRelativeModsExtensionMapping();
    }

    /**
     * Sets the modsExtensionReference
     *
     * @param string $modsExtensionReference
     * @return void
     */
    public function setModsExtensionReference($modsExtensionReference)
    {
        $this->modsExtensionReference = $modsExtensionReference;
    }

    /**
     * Returns the modsExtensionReference
     *
     * @return string $modsExtensionReference
     */
    public function getModsExtensionReference()
    {
        return $this->modsExtensionReference;
    }

    /**
     * Returns the maxIteration
     *
     * @return integer $maxIteration
     */
    public function getMaxIteration()
    {
        return $this->maxIteration;
    }

    /**
     * Sets the maxIteration
     *
     * @param integer $maxIteration
     * @return void
     */
    public function setMaxIteration($maxIteration)
    {
        $this->maxIteration = $maxIteration;
    }

    /**
     * Adds a MetadataObject
     *
     * @param \EWW\Dpf\Domain\Model\MetadataObject $metadataObject
     * @return void
     */
    public function addMetadataObject(\EWW\Dpf\Domain\Model\MetadataObject $metadataObject)
    {
        $this->metadataObject->attach($metadataObject);
    }

    /**
     * Removes a MetadataObject
     *
     * @param \EWW\Dpf\Domain\Model\MetadataObject $metadataObjectToRemove The MetadataObject to be removed
     * @return void
     */
    public function removeMetadataObject(\EWW\Dpf\Domain\Model\MetadataObject $metadataObjectToRemove)
    {
        $this->metadataObject->detach($metadataObjectToRemove);
    }

    /**
     * Returns the metadataObject
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataObject> $metadataObject
     */
    public function getMetadataObject()
    {
        return $this->metadataObject;
    }

    /**
     * Sets the metadataObject
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataObject> $metadataObject
     * @return void
     */
    public function setMetadataObject(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $metadataObject)
    {
        $this->metadataObject = $metadataObject;
    }

    /**
     * Alias for function getMetadataObject()
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataObject> $metadataObject
     */
    public function getChildren()
    {
        return $this->getMetadataObject();
    }

    /**
     * Returns the accessRestrictionRoles
     *
     * @return array $accessRestrictionRoles
     */
    public function getAccessRestrictionRoles()
    {
        if ($this->accessRestrictionRoles) {
            return array_map('trim', explode(',', $this->accessRestrictionRoles));
        } else {
            return array();
        }
    }

    /**
     * Sets the accessRestrictionRoles
     *
     * @param array $accessRestrictionRoles
     * @return void
     */
    public function setAccessRestrictionRoles($accessRestrictionRoles)
    {
        $this->accessRestrictionRoles = implode(',', $accessRestrictionRoles);
    }

    /**
     * Returns the infoText
     *
     * @return string $infoText
     */
    public function getInfoText()
    {
        return $this->infoText;
    }

    /**
     * Sets the infoText
     *
     * @param string $infoText
     * @return void
     */
    public function setInfoText($infoText)
    {
        $this->infoText = $infoText;
    }

}
