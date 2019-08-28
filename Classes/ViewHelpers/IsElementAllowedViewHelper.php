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

use \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use \EWW\Dpf\Security\Security;

class IsElementAllowedViewHelper extends AbstractViewHelper
{
    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return boolean
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        //$pluginName = $renderingContext->getControllerContext()->getRequest()->getPluginName();

        $roles = array();
        if (key_exists('condition', $arguments)) {
            $roles = $arguments['condition'];
            if (!is_array($roles)) return FALSE;
        }

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $security = $objectManager->get(Security::class);
        $clientUserRole = $security->getUserRole();

        //if ($pluginName == "Backoffice" || (key_exists('condition', $arguments) && !$arguments['condition'])) {
        //    return TRUE;
        //}

        if (empty($roles)) {
            return TRUE;
        } else {
            foreach ($roles as $role) {
                if ($role === $clientUserRole) return TRUE;
            }
        }

        return FALSE;
    }


    /**
     *
     * @param array $condition
     *
     */
    public function render($condition)
    {
        return self::renderStatic(
            array('condition' => $condition),
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

}
