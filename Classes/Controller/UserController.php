<?php
namespace EWW\Dpf\Controller;

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

use EWW\Dpf\Domain\Model\FrontendUser;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Controller for the "workspace"/"my publications" area.
 */
class UserController  extends AbstractController
{
    /**
     * benutzerRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FrontendUserRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $frontendUserRepository;

    public function settingsAction() {

        $currentUser = $this->frontendUserRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);

        $this->view->assign('frontendUser', $currentUser);
    }

    /**
     * @param FrontendUser $frontendUser
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function saveSettingsAction(FrontendUser $frontendUser) {
        if ($frontendUser->getFisPersId()) {
            $fisUserService = new \EWW\Dpf\Services\FeUser\FisDataService();
            $fisUserData = $fisUserService->getPersonData($frontendUser->getFisPersId());
            if ($fisUserData == NULL) {
                $frontendUser->setFisPersId("");
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::WARNING;
                $this->addFlashMessage(
                    LocalizationUtility::translate("manager.locallang.user.settings.message.invalidFisId", "dpf"),
                    '',
                    $severity,false
                );
            }
        }

        $this->frontendUserRepository->update($frontendUser);
        $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
        $this->addFlashMessage(
            LocalizationUtility::translate("manager.locallang.user.settings.message.successfullySaved", "dpf"),
            '',
            $severity,false
        );

        $this->forward('settings');
    }
}
