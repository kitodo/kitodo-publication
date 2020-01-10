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
     * frontendUserGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FrontendUserGroupRepository
     * @inject
     */
    protected $frontendUserGroupRepository = null;

    /**
     * frontendUserRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FrontendUserRepository
     * @inject
     */
    protected $frontendUserRepository = null;

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

        // Get frontend user groups of the client.
        $clientFrontendGroups = array();
        foreach ($this->frontendUserGroupRepository->findAll() as $clientGroup) {
            if ($clientGroup->getKitodoRole()) {
                $clientFrontendGroups[$clientGroup->getUid()] = $clientGroup;
            }
        }

        // Get frontend user groups of the user.
        $frontendUserGroups = array();
        $frontendUser = $this->getUser();
        if ($frontendUser) {
            foreach ($frontendUser->getUsergroup() as $userGroup) {
                // Because getUsergroup() does not return objects of the class
                // \EWW\Dpf\Domain\Repository\FrontendUserRepository
                $userGroup = $this->frontendUserGroupRepository->findByUid($userGroup->getUid());
                $frontendUserGroups[$userGroup->getUid()] = $userGroup;
            }
        }

        // Get the roles the user has in the current client.
        $roles = array();
        foreach ($frontendUserGroups as $uid => $group) {
            if (array_key_exists($uid, $clientFrontendGroups)) {
                $roles[$uid] = $group->getKitodoRole();
            }
        }

        if (in_array(self::ROLE_LIBRARIAN, $roles)) return self::ROLE_LIBRARIAN;
        if (in_array(self::ROLE_RESEARCHER, $roles)) return self::ROLE_RESEARCHER;

        return NULL;
    }


}