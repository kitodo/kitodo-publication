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

class IsElementAllowedViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return boolean
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext)
    {
        $pluginName = $renderingContext->getControllerContext()->getRequest()->getPluginName();

        if ($pluginName == "Backoffice" || (key_exists('condition', $arguments) && !$arguments['condition'])) {
            return TRUE;
        }

        return FALSE;
    }


    /**
     *
     * @param boolean $condition
     *
     */
    public function render($condition)
    {
    }

}
