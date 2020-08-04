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

class ExplodeStringViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * Implodes the given array.
     *
     * @param string $string
     * @param string $glue
     *
     * @return array
     */
    public function render($glue, $string)
    {
        if (is_string($string) && !empty($string)) {
            return explode($glue, $string);
        }

        return [];
    }
}
