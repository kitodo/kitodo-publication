<?php
namespace EWW\Dpf\ViewHelpers;

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

class ActiveManagerMenuViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     *
     * @param string $controllerName The controller to be active.
     * @param string $actionName The action to be active.
     */
    public function render($controllerName, $actionName = '')
    {

        if ($this->controllerContext->getRequest()->getControllerName() == $controllerName) {

            if (empty($actionName)) {
                return 'active';
            } elseif ($this->controllerContext->getRequest()->getControllerActionName() == $actionName) {
                return 'active';
            } else {
                return '';
            }
        }

        return '';
    }

}
