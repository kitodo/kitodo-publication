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

use EWW\Dpf\Services\Suggestion\GroupChange;

class DocumentFormField extends AbstractFormElement
{
    protected $file;

    protected $value;

    protected $inputField;

    protected $selectOptions;

    protected $fillOutService;

    protected $defaultInputOption;

    protected $hasDefaultValue = false;

    protected $validation;

    protected $depositLicense = null;

    /**
     * @var array
     */
    protected $licenceOptions = [];

    /**
     * @var \EWW\Dpf\Domain\Model\InputOptionList $inputOptionList
     */
    protected $inputOptionList;

    /**
     * @var string
     */
    protected $validationErrorMessage = '';

    /**
     * @var string
     */
    protected $validator;

    /**
     * @var int
     */
    protected $gndFieldUid;

    /**
     * consent
     *
     * @var boolean
     */
    protected $consent;

    /**
     * @var int
     */
    protected $maxInputLength;

    /**
     * help text
     *
     * @var string
     */
    protected $helpText = '';

    /**
     * @var string
     */
    protected $id = '';

    /**
     * @var string
     */
    protected $objectType = '';

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value, $defaultValue = '')
    {

        $this->hasDefaultValue = !empty($defaultValue);

        if (empty($value)) {
            switch ($this->inputField) {
                case \EWW\Dpf\Domain\Model\MetadataObject::select:
                    if (!empty($defaultValue)) {
                        $this->value = $this->defaultInputOption;
                    } else {
                        $this->value = '';
                    }
                    break;

                case \EWW\Dpf\Domain\Model\MetadataObject::checkbox:
                    if (!empty($defaultValue)) {
                        $this->value = 'yes';
                    } else {
                        $this->value = '';
                    }
                    break;

                default:
                    $this->value = $defaultValue;
                    break;
            }
        } else {
            $this->value = $value;
        }
    }

    public function getInputField()
    {
        return $this->inputField;
    }

    public function setInputField($inputField)
    {
        $this->inputField = $inputField;
    }

    /**
     *
     * @return array
     */
    public function getInputOptions()
    {
        $inputOptions = array();

        if ($this->inputOptionList) {
            $inputOptions[''] = '';
            foreach ($this->inputOptionList->getInputOptions() as $option => $label) {
                $inputOptions[$option] = $label;
            }

            $this->defaultInputOption = trim($this->inputOptionList->getDefaultValue());
        }

        return $inputOptions;
    }

    /**
     * @return \EWW\Dpf\Domain\Model\InputOptionList
     */
    public function getInputOptionList()
    {
        return $this->inputOptionList;
    }

    /**
     *
     * @param \EWW\Dpf\Domain\Model\InputOptionList $inputOptionList
     */
    public function setInputOptionList(\EWW\Dpf\Domain\Model\InputOptionList $inputOptionList = null)
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

    public function getHasDefaultValue()
    {
        return $this->hasDefaultValue;
    }

    public function getValidation()
    {
        return $this->validation;
    }

    public function setValidation($validation)
    {
        $this->validation = $validation;
    }

    /**
     * Gets the data type of the field, e.g. DATE
     *
     * @return string
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Sets the data type of the field, e.g. DATE
     *
     * @param string $validator
     * @return void
     */
    public function setValidator($validator)
    {
        $this->validator = $validator;
    }

    /**
     * Gets the uid of the field which is
     * linked with the gnd field
     *
     * @return int
     */
    public function getGndFieldUid() {
        return $this->gndFieldUid;
    }

    /**
     * Sets the uid of the field which is
     * linked with the gnd field
     *
     * @param int $fieldId
     * @return void
     */
    public function setGndFieldUid($fieldId) {
        $this->gndFieldUid = $fieldId;
    }

    /**
     * Gets the max length of characters for the input field.
     *
     * @return int
     */
    public function getMaxInputLength() {
        return $this->maxInputLength;
    }

    /**
     * Sets the max length of characters for the input field.
     *
     * @return int
     */
    public function setMaxInputLength($maxInputLength) {
        $this->maxInputLength = $maxInputLength;
    }

    /**
     * @return string
     */
    public function getObjectType(): string
    {
        return $this->objectType;
    }

    /**
     * @param string $objectType
     */
    public function setObjectType(string $objectType): void
    {
        $this->objectType = $objectType;
    }

    /**
     * @return mixed
     */
    public function getDepositLicense()
    {
        return $this->depositLicense;
    }

    /**
     * @param mixed $depositLicense
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

    public function getHelpTextLong()
    {
        if ($this->helpText) {
            $domDocument = new \DOMDocument();
            $domDocument->loadXML("<html>".$this->helpText."</html>");
            $xpath = \EWW\Dpf\Helper\XPath::create($domDocument);
            $nodes = $xpath->query("//p");
            if ($nodes->length > 1) {
                $domDocument->firstChild->removeChild($nodes->item(0));
                return str_replace(['<html>','</html>'], '', $domDocument->saveHTML());
            }
        }
    }

    public function getHelpTextShort()
    {
        if ($this->helpText) {
            $domDocument = new \DOMDocument();
            $domDocument->loadXML("<html>" . $this->helpText . "</html>");
            $xpath = \EWW\Dpf\Helper\XPath::create($domDocument);
            $nodes = $xpath->query("//p");
            if ($nodes->length > 0) {
                return $nodes->item(0)->nodeValue;
            }
        }
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param mixed $file
     */
    public function setFile($file): void
    {
        $this->file = $file;
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
     * @return array
     */
    public function getLicenceOptions(): array
    {
        return $this->licenceOptions;
    }

    /**
     * @param array $licenceOptions
     */
    public function setLicenceOptions(array $licenceOptions): void
    {
        $this->licenceOptions = $licenceOptions;
    }

    /**
     * Checks if the current field value is in the list of configured licence options
     *
     * @return bool
     */
    public function isActiveLicenceOption(): bool
    {
        if (is_array($this->licenceOptions)) {
            foreach ($this->licenceOptions as $licenceOption) {
                if ($licenceOption->getUri() === $this->value) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getDisplayValue()
    {
        if ($this->inputOptionList) {
            foreach ($this->inputOptionList->getInputOptions() as $option => $label) {
                $inputOptions[$option] = $label;
            }
            if ($this->getValue()) {
                return $inputOptions[$this->getValue()];
            }
        }

        return $this->getValue();
    }

    /**
     * Applies the given field change
     *
     * @param FieldChange $fieldChange
     */
    public function applyFieldChange(GroupChange $fieldChange)
    {
        foreach ($this->getItems() as $keyField => $valueField) {
            foreach ($valueField as $keyRepeatField => $valueRepeatField) {
                if ($valueRepeatField->getId() === $fieldChange->getGroup()->getId()) {
                    $valueRepeatField->applyChange($fieldChange->getFieldChanges());
                    return;
                }

            }
        }
    }
}
