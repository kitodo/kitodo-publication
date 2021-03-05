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

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use EWW\Dpf\Security\AuthorizationChecker;

class IsAccessGrantedViewHelper extends AbstractViewHelper
{
    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('attribute', 'string', '', true);
        $this->registerArgument('subject', 'mixed', 'A model object or a UID.', true);
        $this->registerArgument(
            'class',
            'string',
            'Model class name, in case of parameter 2 is a UID.',
            false, "EWW\\Dpf\\Domain\\Model\\Document"
        );
    }

    /**
     * Checks if access can be granted for the given attribute and subject.
     *
     * @return bool
     */
    public function render()
    {
        $attribute = $this->arguments['attribute'];
        $subject = $this->arguments['subject'];
        $class = $this->arguments['class'];

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $authorizationChecker = $objectManager->get(AuthorizationChecker::class);

        if (empty($subject)) {
            $subject = $objectManager->get($class);
            return $authorizationChecker->isGranted($attribute, $subject);
        }

        if (is_object($subject)) {
            return $authorizationChecker->isGranted($attribute, $subject);
        }

        $uid = 0;

        if (is_int($subject)) {
            $uid = $subject;
        }

        if (is_string($subject)) {
            list($class, $uid) = explode(":", $subject);
        }

        $repositoryClass = str_replace("Model", "Repository", $class)."Repository";

        $repository = $objectManager->get($repositoryClass);

        if ($repository) {
            $subject = $repository->findByUid($uid);
        }

        if ($subject instanceof $class) {
            $authorizationChecker = $objectManager->get(AuthorizationChecker::class);
            
            return $authorizationChecker->isGranted($attribute, $subject);
        }

        return FALSE;
    }

}
