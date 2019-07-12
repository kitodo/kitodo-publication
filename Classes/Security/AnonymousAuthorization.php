<?php
namespace EWW\Dpf\Security;

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


class AnonymousAuthorization extends Authorization
{
    /**
     *
     * @param string $arttribute
     */
    public function checkAttributePermission($attribute)
    {
        switch ($attribute) {
            case 'EWW\Dpf\Controller\DocumentFormController::listAction':
            case 'EWW\Dpf\Controller\DocumentFormController::cancelAction':
            case 'EWW\Dpf\Controller\DocumentFormController::newAction':
            case 'EWW\Dpf\Controller\DocumentFormController::createAction':
            case 'EWW\Dpf\Controller\AjaxDocumentFormController::primaryUploadAction':
            case 'EWW\Dpf\Controller\AjaxDocumentFormController::secondaryUploadAction':
            case 'EWW\Dpf\Controller\AjaxDocumentFormController::deleteFileAction':
            case 'EWW\Dpf\Controller\AjaxDocumentFormController::fillOutAction':
            case 'EWW\Dpf\Controller\AjaxDocumentFormController::fieldAction':
            case 'EWW\Dpf\Controller\AjaxDocumentFormController::groupAction': {
                return TRUE;
                break;
            }

            default: return FALSE;
        }
    }
}