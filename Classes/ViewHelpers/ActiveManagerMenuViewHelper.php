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

class ActiveManagerMenuViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{

    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('controllerName', 'string',
            'The controller to be active.', true
        );
        $this->registerArgument('actionNames', 'array',
            'The actions to be active.', false, []
        );
    }

    /**
     * @return string
     */
    public function render()
    {
        $controllerName = $this->arguments['controllerName'];
        $actionNames = $this->arguments['actionNames'];

        $controllerContext = $this->renderingContext->getControllerContext();

        if ($controllerContext->getRequest()->getControllerName() == $controllerName) {

            if (empty($actionNames)) {
                return 'active';
            } elseif (in_array($controllerContext->getRequest()->getControllerActionName(), $actionNames)) {
                return 'active';
            } else {
                return '';
            }
        }

        return '';
    }

}
