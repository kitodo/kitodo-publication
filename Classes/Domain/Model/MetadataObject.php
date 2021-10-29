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
     * @var string
     */
    protected $objectType = '';
    /**
     * depositLicense
     *
     * @var int
     */
    protected $depositLicense = '';

    /**
     * @var string
     */
    protected $licenceOptions = '';

    /**
     * JSON mapping
     *
     * @var string
     */
    protected $jsonMapping = '';

    const input    = 0;
    const textarea = 1;
    const select   = 2;
    const checkbox = 3;
    const hidden   = 4;
    const textareaMarkdown = 10;
    const INPUTDROPDOWN = 100;
    const FILE_UPLOAD = 200;
    const LICENCE_CONSENT = 300;

    const VALIDATOR_REGEXP = "REGEXP";
    const VALIDATOR_DATE   = "DATE";
    const VALIDATOR_REMOTE_FILE_EXISTS = "REMOTE_FILE_EXISTS";

    /**
     * validator
     *
     * @var string
     */
    protected $validator;

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
    const FILL_OUT_AUTOCOMPLETE = 'autocomplete';

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
     * @var string
     */
    protected $validationErrorMessage = '';

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
     * fis mapping
     *
     * @var string
     */
    protected $fisPersonMapping = '';

    /**
     * fis mapping
     *
     * @var string
     */
    protected $fisOrganisationMapping = '';

    /**
     * gnd mapping
     *
     * @var string
     */
    protected $gndPersonMapping = '';

    /**
     * gnd mapping
     *
     * @var string
     */
    protected $gndOrganisationMapping = '';

    /**
     * ror mapping
     *
     * @var string
     */
    protected $rorMapping = '';

    /**
     * zdb mapping
     *
     * @var string
     */
    protected $zdbMapping = '';

    /**
     * unpaywall mapping
     * @var string
     */
    protected $unpaywallMapping = '';

    /**
     * orcid person mapping
     * @var string
     */
    protected $orcidPersonMapping = '';

    /**
     * help text
     *
     * @var string
     */
    protected $helpText = '';

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
        if ($this->isRepeatable()) {
            return $this->maxIteration;
        }
        return 1;
    }

    /**
     * Sets the maxIteration
     *
     * @param integer $maxIteration
     * @return void
     */
    public function setMaxIteration($maxIteration)
    {
        if ($this->isRepeatable()) {
            $this->maxIteration = $maxIteration;
        } else {
            $this->maxIteration = 1;
        }
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
        $this->modsExtension = boolval($modsExtension);
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
        return trim($this->mapping, " /");
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
        $this->consent = boolval($consent);
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
     * @return string
     */
    public function getValidationErrorMessage(): string
    {
        return $this->validationErrorMessage;
    }

    /**
     * @param string $validationErrorMessage
     */
    public function setValidationErrorMessage(string $validationErrorMessage): void
    {
        $this->validationErrorMessage = $validationErrorMessage;
    }

    /**
     * Returns the validator
     *
     * @return string $validator
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Sets the validator
     *
     * @param string $validator
     * @return void
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
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
        $this->embargo = boolval($embargo);
    }

    /**
     * @return string
     */
    public function getFisPersonMapping(): string
    {
        return $this->fisPersonMapping;
    }

    /**
     * @param string $fisPersonMapping
     */
    public function setFisPersonMapping(string $fisPersonMapping): void
    {
        $this->fisPersonMapping = $fisPersonMapping;
    }

    /**
     * @return string
     */
    public function getFisOrganisationMapping(): string
    {
        return $this->fisOrganisationMapping;
    }

    /**
     * @param string $fisOrganisationMapping
     */
    public function setFisOrganisationMapping(string $fisOrganisationMapping): void
    {
        $this->fisOrganisationMapping = $fisOrganisationMapping;
    }

    /**
     * @return string
     */
    public function getRorMapping(): string
    {
        return $this->rorMapping;
    }

    /**
     * @param string $rorMapping
     */
    public function setRorMapping(string $rorMapping)
    {
        $this->rorMapping = $rorMapping;
    }

    /**
     * @return string
     */
    public function getZdbMapping(): string
    {
        return $this->zdbMapping;
    }

    /**
     * @param string $zdbMapping
     */
    public function setZdbMapping(string $zdbMapping): void
    {
        $this->zdbMapping = $zdbMapping;
    }

    /**
     * @return string
     */
    public function getUnpaywallMapping(): string
    {
        return $this->unpaywallMapping;
    }

    /**
     * @param string $unpaywallMapping
     */
    public function setUnpaywallMapping(string $unpaywallMapping): void
    {
        $this->unpaywallMapping = $unpaywallMapping;
    }

    /**
     * @return string
     */
    public function getObjectType(): string
    {
        return $this->objectType;
    }
    /**
     * Gets the jsonMapping
     *
     * @return string
     */
    public function getJsonMapping(): string
    {
        return $this->jsonMapping;
    }

    /**
     * Sets the jsonMapping
     *
     * @param string $jsonMapping
     */
    public function setJsonMapping(string $jsonMapping): void
    {
        $this->jsonMapping = $jsonMapping;
    }
    /**
     * @param string $objectType
     */
    public function setObjectType(string $objectType): void
    {
        $this->objectType = $objectType;
    }

    /**
     * @return string
     */
    public function getGndPersonMapping(): string
    {
        return $this->gndPersonMapping;
    }

    /**
     * @param string $gndPersonMapping
     */
    public function setGndPersonMapping(string $gndPersonMapping): void
    {
        $this->gndPersonMapping = $gndPersonMapping;
    }

    /**
     * @return string
     */
    public function getGndOrganisationMapping(): string
    {
        return $this->gndOrganisationMapping;
    }

    /**
     * @param string $gndOrganisationMapping
     */
    public function setGndOrganisationMapping(string $gndOrganisationMapping): void
    {
        $this->gndOrganisationMapping = $gndOrganisationMapping;
    }

    /**
     * @return string
     */
    public function getOrcidPersonMapping(): string
    {
        return $this->orcidPersonMapping;
    }

    /**
     * @param string $orcidPersonMapping
     */
    public function setOrcidPersonMapping(string $orcidPersonMapping): void
    {
        $this->orcidPersonMapping = $orcidPersonMapping;
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

    /**
     * @return string
     */
    public function getHelpText(): string
    {
        return $this->helpText;
    }

    /**
     * @param string $helpText
     */
    public function setHelpText(string $helpText): void
    {
        $this->helpText = $helpText;
    }

    /**
     * @return bool
     */
    public function isUploadField()
    {
        return $this->getInputField() == self::FILE_UPLOAD;
    }

    /**
     * @return bool
     */
    public function isFileLabelField()
    {
        return $this->getObjectType() == 'fileLabel';
    }

    /**
     * @return bool
     */
    public function isFileDownloadField()
    {
        return $this->getObjectType() == 'fileDownload';
    }

    /**
     * @return bool
     */
    public function isFileArchiveField()
    {
        return $this->getObjectType() == 'fileArchive';
    }

    protected function isRepeatable() {
        return !in_array($this->getObjectType(), ['fileDownload','fileArchive','fileLabel'])
            && $this->getInputField() != self::FILE_UPLOAD;
    }

    /**
     * @return string
     */
    public function getLicenceOptions(): string
    {
        return $this->licenceOptions;
    }

    /**
     * @param string $licenceOptions
     */
    public function setLicenceOptions(string $licenceOptions): void
    {
        $this->licenceOptions = $licenceOptions;
    }
}
