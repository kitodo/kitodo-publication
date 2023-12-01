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
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $frontendUserRepository = null;

    /**
     * frontendUserGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FrontendUserGroupRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $frontendUserGroupRepository = null;

    const ROLE_ANONYMOUS = "ROLE_ANONYMOUS";
    const ROLE_RESEARCHER = "ROLE_RESEARCHER";
    const ROLE_LIBRARIAN = "ROLE_LIBRARIAN";
    const ROLE_ADMIN = "ROLE_ADMIN";

    /**
     * Gets the current logged in frontend user
     *
     * @return null|\EWW\Dpf\Domain\Model\FrontendUser
     */
    public function getUser()
    {
        $token = $GLOBALS['_GET']['tx_dpf_rest_api']['token'];
        $user = $GLOBALS['TSFE']->fe_user->user;
        if (!empty($user) && is_array($user) && array_key_exists('uid', $user)) {
            return $this->frontendUserRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
        } else if ($token) {
            $token = htmlentities($token);
            $token = addslashes($token);
            return $this->frontendUserRepository->findOneByApiToken($token);
        } else {
            return NULL;
        }
    }

    /**
     *
     */
    public function getUserAccessToGroups() {
        if ($this->getUser()) {
            $frontendUser = $this->getUser();
            $userGroups = $frontendUser->getUsergroup();
            $accessToIds = [];
            foreach ($userGroups as $userGroup) {
                // Because getUsergroup() does not return objects of the class
                // \EWW\Dpf\Domain\Model\FrontendUserRepository
                $userGroup = $this->frontendUserGroupRepository->findByUid($userGroup->getUid());
                if (!empty($userGroup->getAccessToGroups())) {
                    $accessToIds = array_merge($accessToIds, explode(',', $userGroup->getAccessToGroups()));
                }

                // get first subgroups // TODO How deep? Recursion needed?
                $subGroups = $userGroup->getSubgroup();
                if ($subGroups) {
                    foreach ($subGroups as $subGroup) {
                        $subGroup = $this->frontendUserGroupRepository->findByUid($subGroup->getUid());
                        if (!empty($subGroup->getAccessToGroups())) {
                            $accessToIds = array_merge($accessToIds, explode(',', $subGroup->getAccessToGroups()));
                        }
                    }
                }
            }
            if (empty($accessToIds[0])) {
                return null;
            } else {
                return $accessToIds;
            }
        }
        return NULL;
    }

    /**
     * Gets the role of the current frontend user
     * @return string
     */
    public function getUserRole()
    {
        if ($this->getUser()) {
            return $this->getUser()->getUserRole();
        }
        return '';
    }

    /**
     * Gets the name of the current frontend user
     * @return string
     */
    public function getUsername()
    {
        if ($this->getUser()) {
            return $this->getUser()->getUsername();
        }
        return '';
    }

    /**
     * Gets the fis person id of the current frontend user
     * @return string
     */
    public function getFisPersId()
    {
        if ($this->getUser()) {
            return $this->getUser()->getFisPersId();
        }
        return '';
    }

}
