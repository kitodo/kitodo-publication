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

use EWW\Dpf\Domain\Model\Document;

class DocumentFormBackofficeVoter extends Voter
{
    const BACKOFFICE_DOCUMENTFORMBACKOFFICE_LIST = "BACKOFFICE_DOCUMENTFORMBACKOFFICE_LIST";
    const BACKOFFICE_DOCUMENTFORMBACKOFFICE_CANCEL = "BACKOFFICE_DOCUMENTFORMBACKOFFICE_CANCEL";
    const BACKOFFICE_DOCUMENTFORMBACKOFFICE_CANCELEDIT = "BACKOFFICE_DOCUMENTFORMBACKOFFICE_CANCELEDIT";

    /**
     * DocumentFormBackofficeVoter constructor.
     */
    public function __construct()
    {
        $this->attributes = array(
            self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_LIST,
            self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_CANCEL,
            self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_CANCELEDIT,
        );
    }

    /**
     * Determines if the voter supports the given attribute.
     *
     * @param string $attribute
     * @param mixed $subject
     * @return mixed
     */
    public function supports($attribute, $subject = NULL)
    {
        if (!in_array($attribute, $this->attributes)) {
            return FALSE;
        }

        if (!$subject instanceof DocumentForm && !$subject instanceof Document && !is_null($subject)) {
            return FALSE;
        }

        return TRUE;
    }


    /**
     * Determines if access for the given attribute and subject is allowed.
     *
     * @param string $attribute
     * @param mixed $subject
     * @return mixed
     */
    public function voteOnAttribute($attribute, $subject = NULL)
    {

        switch ($attribute) {

            case self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_LIST:
                return $this->defaultAccess();
                break;


            case self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_CANCEL:
                return $this->defaultAccess();
                break;

            case self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_CANCELEDIT:
                return $this->defaultAccess();
                break;

        }

        throw new \Exception('An unexpected error occurred!');
    }

    /**
     * @return bool
     */
    protected function defaultAccess()
    {
        if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {
            return TRUE;
        }

        if ($this->security->getUserRole() === Security::ROLE_RESEARCHER) {
            return TRUE;
        }

        return FALSE;
    }
}