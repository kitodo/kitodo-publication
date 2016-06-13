<?php
namespace EWW\Dpf\Validation;

class DocumentFormValidator extends \TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator
{
    /**
     * action isValid
     *
     * @param string $value
     * @return boolean
     */
    public function isValid($value)
    {

        $this->errors = array();

        $this->addError("Qucosa Fehler");
        return false;
    }
}
