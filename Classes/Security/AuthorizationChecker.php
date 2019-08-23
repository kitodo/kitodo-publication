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

use EWW\Dpf\Domain\Model\LocalDocumentStatus;

class AuthorizationChecker
{
    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager = null;

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
     * @param string $attribute
     * @param string $plugin
     * @return bool
     */
    public function isGranted($attribute, $plugin = NULL) {

        $clientUserRoles = $this->getClientUserRoles();
        $clientUserRoles[] = self::ROLE_ANONYMOUS;

        foreach ($clientUserRoles as $role) {

            $roleAuthorization = $this->getAuthorizationByRole($role);
            if ($roleAuthorization && $roleAuthorization->checkAttributePermission($attribute)) {
                return TRUE;
            } else {
                continue;
            }
        }

        return FALSE;

    }

    /**
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param string $action
     * @return bool
     */
    public function hasDocumentAccessRight(\EWW\Dpf\Domain\Model\Document $document, $action) {

        $clientUserRoles = $this->getClientUserRoles();

        if (
            in_array(
                self::ROLE_LIBRARIAN,
                $clientUserRoles
            )
        ) {
            switch ($document->getLocalStatus()) {
                case LocalDocumentStatus::NEW:
                    return $document->getOwner() === $this->getUser()->getUid();
                    break;

                default:
                    return TRUE;
                    break;
            }
        } elseif (
            in_array(
                self::ROLE_RESEARCHER,
                $clientUserRoles
            )
        ) {
            if ($document->getOwner() === $this->getUser()->getUid()) {

            } else {
                return FALSE;
            }
        }

        return FALSE;
    }

    /**
     * Get the roles the user has in the current client
     *
     * @return array
     */
    public function getClientUserRoles() {

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

        return $roles;

    }

    /**
     * Gets an authorization object associated with the given role
     *
     * @param string $role
     */
    protected function getAuthorizationByRole($role)
    {

       if (strpos($role, 'ROLE_') === 0) {
           $authorizationPrefix = ucfirst(strtolower(str_replace('ROLE_', '', $role)));

           if ($authorizationPrefix) {
               $authorizationClass = $authorizationPrefix . 'Authorization';
               $authorizationClass = 'EWW\\Dpf\\Security\\' . $authorizationClass;

               if (class_exists($authorizationClass)) {
                   return $this->objectManager->get($authorizationClass);
               }
           }
       }

        return NULL;
    }

    /**
     * Gets the logged in user
     *
     * @return mixed
     */
    public function getUser() {
        return $this->frontendUserRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);
    }



}