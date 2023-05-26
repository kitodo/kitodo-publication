<?php
namespace EWW\Dpf\ViewHelpers;

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

class ArrayViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('key', 'mixed', 'The searched value.', true);
        $this->registerArgument('array', 'array', 'The array.', true);
    }

    /**
     * @return bool
     */
    public function render()
    {
        $key = $this->arguments['key'];
        $array = $this->arguments['array'];

        if (array_key_exists($key, $array)) {
            return $array[$key];
        } else {
            return "";
        }
    }

}
