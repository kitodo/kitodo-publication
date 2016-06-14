<?php
namespace EWW\Dpf\Validation;

/**
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
