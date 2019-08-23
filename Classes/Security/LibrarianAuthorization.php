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


class LibrarianAuthorization extends Authorization
{
    /**
     *
     * @param string $arttribute
     */
    public function checkAttributePermission($attribute)
    {
        switch ($attribute) {
            case 'EWW\Dpf\Controller\DocumentController::showDetailsAction':
            case 'EWW\Dpf\Controller\DocumentController::cancelListTaskAction':
            case 'EWW\Dpf\Controller\DocumentController::listAction':
            case 'EWW\Dpf\Controller\DocumentController::listRegisteredAction':
            case 'EWW\Dpf\Controller\DocumentController::listInProgressAction':
            case 'EWW\Dpf\Controller\DocumentController::discardAction':
            case 'EWW\Dpf\Controller\DocumentController::duplicateAction':
            case 'EWW\Dpf\Controller\DocumentController::releaseAction':
            case 'EWW\Dpf\Controller\DocumentController::activateAction':
            case 'EWW\Dpf\Controller\DocumentController::inactivateAction':
            case 'EWW\Dpf\Controller\DocumentController::deleteAction':
            case 'EWW\Dpf\Controller\DocumentController::restoreAction':
            case 'EWW\Dpf\Controller\DocumentController::registerAction':
            case 'EWW\Dpf\Controller\DocumentController::uploadFilesAction':
            case 'EWW\Dpf\Controller\DocumentController::deleteLocallyAction':
            case 'EWW\Dpf\Controller\DocumentFormBackofficeController::listAction':
            case 'EWW\Dpf\Controller\DocumentFormBackofficeController::deleteAction':
            case 'EWW\Dpf\Controller\DocumentFormBackofficeController::cancelAction':
            case 'EWW\Dpf\Controller\DocumentFormBackofficeController::cancelEditAction':
            case 'EWW\Dpf\Controller\DocumentFormBackofficeController::editAction':
            case 'EWW\Dpf\Controller\DocumentFormBackofficeController::updateAction':
            case 'EWW\Dpf\Controller\DocumentFormBackofficeController::newAction':
            case 'EWW\Dpf\Controller\DocumentFormBackofficeController::createAction':
            case 'EWW\Dpf\Controller\SearchController::doubletCheckAction':
            case 'EWW\Dpf\Controller\SearchController::listAction':
            case 'EWW\Dpf\Controller\SearchController::searchAction':
            case 'EWW\Dpf\Controller\SearchController::extendedSearchAction':
            case 'EWW\Dpf\Controller\SearchController::nextResultsAction':
            case 'EWW\Dpf\Controller\SearchController::latestAction':
            case 'EWW\Dpf\Controller\SearchController::importAction':
            case 'EWW\Dpf\Controller\SearchController::updateIndexAction': {
                return TRUE;
                break;
            }

            default: return FALSE;
        }
    }
}