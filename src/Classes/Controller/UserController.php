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

/**
 * Controller for the "workspace"/"my publications" area.
 */
class UserController  extends AbstractController
{
    /**
     * benutzerRepository
     *
     * @var EWW\Dpf\Domain\Repository\FrontendUserRepository
     * @inject
     */
    protected $frontendUserRepository;

    public function settingsAction() {

        $currentUser = $this->frontendUserRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);

        $this->view->assign('frontendUser', $currentUser);
    }

    public function saveSettingsAction(FrontendUser $frontendUser) {

        $this->frontendUserRepository->update($frontendUser);
        $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
        $this->addFlashMessage("Success", '', $severity,false);

        $this->forward('settings');
    }

}
