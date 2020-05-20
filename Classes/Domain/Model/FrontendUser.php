<?php
namespace EWW\Dpf\Domain\Model;

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

use EWW\Dpf\Security\Security;

/**
* Frontend user
*/
class FrontendUser extends \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
{
    /**
     * frontendUserGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FrontendUserGroupRepository
     * @inject
     */
    protected $frontendUserGroupRepository = null;


    /**
     * Get the role the user has in the current client
     *
     * @return string
     */
    public function getUserRole()
    {
        // Get frontend user groups of the client.
        $clientFrontendGroups = array();
        foreach ($this->frontendUserGroupRepository->findAll() as $clientGroup) {
            if ($clientGroup->getKitodoRole()) {
                $clientFrontendGroups[$clientGroup->getUid()] = $clientGroup;
            }
        }

        // Get frontend user groups of the user.
        $frontendUserGroups = array();
        foreach ($this->getUsergroup() as $userGroup) {
            // Because getUsergroup() does not return objects of the class
            // \EWW\Dpf\Domain\Model\FrontendUserRepository
            $userGroup = $this->frontendUserGroupRepository->findByUid($userGroup->getUid());
            $frontendUserGroups[$userGroup->getUid()] = $userGroup;
        }

        // Get the roles the user has in the current client.
        $roles = array();
        foreach ($frontendUserGroups as $uid => $group) {
            if (array_key_exists($uid, $clientFrontendGroups)) {
                $roles[$uid] = $group->getKitodoRole();
            }
        }

        if (in_array(Security::ROLE_LIBRARIAN, $roles)) {
            return Security::ROLE_LIBRARIAN;
        }

        if (in_array(Security::ROLE_RESEARCHER, $roles)) {
            return Security::ROLE_RESEARCHER;
        }

        return "";
    }

}