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

class Security
{
    /**
     * frontendUserRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FrontendUserRepository
     * @inject
     */
    protected $frontendUserRepository = null;

    /**
     * frontendUserHelper
     *
     * @var \EWW\Dpf\Helper\FrontendUserHelper
     * @inject
     */
    protected $frontendUserHelper = null;


    const ROLE_ANONYMOUS = "ROLE_ANONYMOUS";
    const ROLE_RESEARCHER = "ROLE_RESEARCHER";
    const ROLE_LIBRARIAN = "ROLE_LIBRARIAN";


    /**
     * Gets the current logged in frontend user
     *
     * @return null|\EWW\Dpf\Domain\Model\FrontendUser
     */
    public function getUser()
    {
        $user = $GLOBALS['TSFE']->fe_user->user;
        if (!empty($user) && is_array($user) && array_key_exists('uid', $user)) {
            return $this->frontendUserRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        } else {
            return NULL;
        }
    }

    /**
     * Get the role the user has in the current client
     *
     * @return string
     */
    public function getUserRole() {
        return $this->frontendUserHelper->getUserRole($this->getUser()->getUid());
    }


}