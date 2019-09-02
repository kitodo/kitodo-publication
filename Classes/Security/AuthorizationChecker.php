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
     * $documentVoter
     *
     * @var \EWW\Dpf\Security\DocumentVoter
     * @inject
     */
    protected $documentVoter = null;


    const ROLE_ANONYMOUS = "ROLE_ANONYMOUS";
    const ROLE_RESEARCHER = "ROLE_RESEARCHER";
    const ROLE_LIBRARIAN = "ROLE_LIBRARIAN";


    public function denyAccessUnlessGranted($attribute, $subject = NULL)
    {
        $voters[] = $this->objectManager->get(\EWW\Dpf\Security\DocumentVoter::class);
        $voters[] = $this->objectManager->get(\EWW\Dpf\Security\DocumentFormBackofficeVoter::class);
        $voters[] = $this->objectManager->get(\EWW\Dpf\Security\SearchVoter::class);

        foreach ($voters as $voter) {
            if ($voter->supports($attribute, $subject)) {
                if($voter->voteOnAttribute($attribute, $subject)) {
                    return;
                } else {
                    throw new \Exception("Access denied!");
                }
            }
        }

        throw new \Exception("Access denied!");
    }


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




}