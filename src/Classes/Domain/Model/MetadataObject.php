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
 * MetadataObject
 */
class MetadataObject extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity implements MetadataMandatoryInterface
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
     * maxIteration
     *
     * @var integer
     */
    protected $maxIteration = 0;

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
     * inputField
     *
     * @var integer
     */
    protected $inputField = 0;

    /**
     * depositLicense
     *
     * @var int
     */
    protected $depositLicense = '';


    const input    = 0;
    const textarea = 1;
    const select   = 2;
    const checkbox = 3;
    const hidden   = 4;
    const INPUTDROPDOWN = 100;

    const INPUT_DATA_TYPE_REGEXP = "REGEXP";
    const INPUT_DATA_TYPE_DATE   = "DATE";

    /**
     * dataType
     *
     * @var string
     */
    protected $dataType;

    /**
     * modsExtension
     *
     * @var boolean
     */
    protected $modsExtension = false;

    /**
     * inputOptionList
     *
     * @var \EWW\Dpf\Domain\Model\InputOptionList
     */
    protected $inputOptionList = null;

    /**
     * fillOutService
     *
     * @var string
     */
    protected $fillOutService = '';

    const FILL_OUT_SERVICE_URN = 'urn';
    const FILL_OUT_SERVICE_GND = 'gnd';

    /**
     * @var string
     */
    protected $gndFieldUid = '';

    /**
     * accessRestrictionRoles
     *
     * @var string
     */
    protected $accessRestrictionRoles = '';

    /**
     * consent
     *
     * @var boolean
     */
    protected $consent;

    /**
     * defaultValue
     *
     * @var string
     */
    protected $defaultValue;

    /**
     * validation
     *
     * @var string
     */
    protected $validation = '';


    /**
     * max input length
     *
     * @var integer
     */
    protected $maxInputLength = 0;

    /**
     * Embargo field option
     *
     * @var boolean
     */
    protected $embargo;


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
     * Returns the modsExtension
     *
     * @return boolean $modsExtension
     */
    public function getModsExtension()
    {
        return $this->modsExtension;
    }

    /**
     * Sets the modsExtension
     *
     * @param boolean $modsExtension
     * @return void
     */
    public function setModsExtension($modsExtension)
    {
        $this->modsExtension = $modsExtension;
    }

    /**
     * Returns the boolean state of modsExtension
     *
     * @return boolean
     */
    public function isModsExtension()
    {
        return $this->modsExtension;
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
     * Returns the relative mapping
     *
     * @return string $relativeMapping
     */
    public function getRelativeMapping()
    {
        $modsRegExp = "/^.*?mods:mods/i";
        $mapping    = preg_replace($modsRegExp, "", $this->mapping);
        return trim($mapping, " /");
    }

    /**
     * Returns the inputField
     *
     * @return integer $inputField
     */
    public function getInputField()
    {
        return $this->inputField;
    }

    /**
     * Sets the inputField
     *
     * @param integer $inputField
     * @return void
     */
    public function setInputField($inputField)
    {
        $this->inputField = $inputField;
    }

    /**
     * Returns always NULL because an Object never has children.
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\MetadataObject> $metadataObject
     */
    public function getChildren()
    {
        return null;
    }

    /**
     * Returns the inputOptionList
     *
     * @return \EWW\Dpf\Domain\Model\InputOptionList $inputOptionList
     */
    public function getInputOptionList()
    {
        return $this->inputOptionList;
    }

    /**
     * Sets the inputOptionList
     *
     * @param \EWW\Dpf\Domain\Model\InputOptionList $inputOptionList
     * @return void
     */
    public function setInputOptionList(\EWW\Dpf\Domain\Model\InputOptionList $inputOptionList)
    {
        $this->inputOptionList = $inputOptionList;
    }

    /**
     * Returns the fillOutService
     *
     * @return string $fillOutService
     */
    public function getFillOutService()
    {
        return $this->fillOutService;
    }

    /**
     * Sets the fillOutService
     *
     * @param string $fillOutService
     * @return void
     */
    public function setFillOutService($fillOutService)
    {
        $this->fillOutService = $fillOutService;
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
     * Returns the consent
     *
     * @return boolean $consent
     */
    public function getConsent()
    {
        return $this->consent;
    }

    /**
     * Sets the consent
     *
     * @param boolean $consent
     * @return void
     */
    public function setConsent($consent)
    {
        $this->consent = $consent;
    }

    /**
     * Returns the defaultValue
     *
     * @return string $defaultValue
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * Sets the defaultValue
     *
     * @param string $defaultValue
     * @return void
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * Returns the validation
     *
     * @return string $validation
     */
    public function getValidation()
    {
        return $this->validation;
    }

    /**
     * Sets the validation
     *
     * @param string $validation
     * @return void
     */
    public function setValidation($validation)
    {
        $this->validation = $validation;
    }

    /**
     * Returns the dataType
     *
     * @return string $dataType
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * Sets the dataType
     *
     * @param string $dataType
     * @return void
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
    }

    /**
     * @return string
     */
    public function getGndFieldUid()
    {
        return $this->gndFieldUid;
    }

    /**
     * @param string $gndFieldUid
     */
    public function setGndFieldUid($gndFieldUid)
    {
        $this->gndFieldUid = $gndFieldUid;
    }

    /**
     * @return integer
     */
    public function getMaxInputLength()
    {
        if ($this->maxInputLength == 0) {
            if ($this->inputField == self::input) {
                return 255;
            } else {
                return 2048;
            }
        } else {
            return $this->maxInputLength;
        }
    }

    /**
     * @return integer
     */
    public function setMaxInputLength($maxInputLength)
    {
        $this->maxInputLength = $maxInputLength;
    }

    /**
     * @return bool
     */
    public function getEmbargo(): bool
    {
        return $this->embargo;
    }

    /**
     * @param bool $embargo
     */
    public function setEmbargo(bool $embargo)
    {
        $this->embargo = $embargo;
    }

    /**
     * @return int
     */
    public function getDepositLicense(): int
    {
        return $this->depositLicense;
    }

    /**
     * @param int $depositLicense
     */
    public function setDepositLicense($depositLicense): void
    {
        $this->depositLicense = $depositLicense;
    }
}
