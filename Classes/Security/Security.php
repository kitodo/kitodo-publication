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
