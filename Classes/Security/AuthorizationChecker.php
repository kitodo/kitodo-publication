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


class AuthorizationChecker
{
    /**
     * objectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $objectManager;

    /**
     * security
     *
     * @var \EWW\Dpf\Security\Security
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $security = null;

    public function denyAccessUnlessLoggedIn()
    {
        $security = $this->objectManager->get(\EWW\Dpf\Security\Security::class);

        if (
            $this->security->getUserRole() === Security::ROLE_LIBRARIAN ||
            $this->security->getUserRole() === Security::ROLE_RESEARCHER
        ) {
            return;
        } else {
            header('Temporary-Header: True', true, 403);
            header_remove('Temporary-Header');
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.access_denied';
            $accessDeniedMessage = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf');
            die($accessDeniedMessage);
        }
    }

    public function denyAccessUnlessGranted($attribute, $subject = NULL)
    {
        if($this->isGranted($attribute, $subject)) {
            return;
        } else {
            header('Temporary-Header: True', true, 403);
            header_remove('Temporary-Header');
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.access_denied';
            $accessDeniedMessage = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf');
            die($accessDeniedMessage);
        }
    }


    /**
     * @param string $attribute
     * @param object $subject
     * @return bool
     */
    public function isGranted($attribute, $subject = NULL) {
        $voters = Voter::getVoters();

        foreach ($voters as $voter) {
            if ($voter->supports($attribute, $subject)) {
                return $voter->voteOnAttribute($attribute, $subject);
            }
        }

        return FALSE;
    }

}