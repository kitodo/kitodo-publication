<?php
namespace EWW\Dpf\ViewHelpers\Document;

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

class InputOptionsViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     *
     * @param \Eww\Dpf\Domain\Model\InputOptionList $inputOptionList
     *
     * @return string Rendered string
     * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
     */
    public function render($inputOptionList)
    {
        $output = "";

        $inputOptions = array();

        if ($inputOptionList) {
            $inputOptions[''] = '';
            foreach ($inputOptionList->getInputOptions() as $option => $label) {
                $inputOptions[$option] = $label;
            }

            //$this->defaultInputOption = trim($inputOptionList->getDefaultValue());
        }

        return $inputOptions;
    }
}