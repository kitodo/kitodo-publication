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

class ImplodeArrayViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * Implodes the given array.
     *
     * @param mixed $array
     * @param string $glue
     *
     * @return string
     */
    public function render($glue, $array)
    {
        if (is_array($array) && sizeof($array) > 0) {
            return implode($glue, $array);
        }

        return "";
    }
}
