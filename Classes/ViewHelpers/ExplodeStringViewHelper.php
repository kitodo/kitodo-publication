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

class ExplodeStringViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('string', 'string', '', true);
        $this->registerArgument('glue', 'string', '', true);
    }

    /**
     * Explodes the given string.
     *
     * @return array
     */
    public function render()
    {
        $string = $this->arguments['string'];
        $glue = $this->arguments['glue'];

        if (is_string($string) && !empty($string)) {
            return explode($glue, $string);
        }

        return [];
    }
}
