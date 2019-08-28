<?php
/**
 * Created by PhpStorm.
 * User: hauke
 * Date: 28.08.19
 * Time: 12:49
 */

namespace EWW\Dpf\Security;

use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Model\LocalDocumentStatus;
use EWW\Dpf\Security\Security;

class DocumentFormBackofficeVoter extends Voter
{
    const BACKOFFICE_DOCUMENTFORMBACKOFFICE_LIST = "BACKOFFICE_DOCUMENTFORMBACKOFFICE_LIST";
    const BACKOFFICE_DOCUMENTFORMBACKOFFICE_NEW = "BACKOFFICE_DOCUMENTFORMBACKOFFICE_NEW";
    const BACKOFFICE_DOCUMENTFORMBACKOFFICE_CREATE = "BACKOFFICE_DOCUMENTFORMBACKOFFICE_CREATE";
    const BACKOFFICE_DOCUMENTFORMBACKOFFICE_EDIT = "BACKOFFICE_DOCUMENTFORMBACKOFFICE_EDIT";
    const BACKOFFICE_DOCUMENTFORMBACKOFFICE_UPDATE = "BACKOFFICE_DOCUMENTFORMBACKOFFICE_UPDATE";
    const BACKOFFICE_DOCUMENTFORMBACKOFFICE_CANCEL = "BACKOFFICE_DOCUMENTFORMBACKOFFICE_CANCEL";
    const BACKOFFICE_DOCUMENTFORMBACKOFFICE_CANCELEDIT = "BACKOFFICE_DOCUMENTFORMBACKOFFICE_CANCELEDIT";
    const BACKOFFICE_DOCUMENTFORMBACKOFFICE_DELETE = "BACKOFFICE_DOCUMENTFORMBACKOFFICE_DELETE";

    /**
     * DocumentFormBackofficeVoter constructor.
     */
    public function __construct()
    {
        $this->attributes = array(
            self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_LIST,
            self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_NEW,
            self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_CREATE,
            self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_EDIT,
            self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_UPDATE,
            self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_CANCEL,
            self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_CANCELEDIT,
            self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_DELETE
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

            case self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_NEW:
                return $this->defaultAccess();
                break;

            case self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_CREATE:
                return $this->defaultAccess();
                break;

            case self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_EDIT:
                return $this->canEdit($subject);
                break;

            case self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_UPDATE:
                return $this->canUpdate($subject);
                break;

            case self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_CANCEL:
                return $this->defaultAccess();
                break;

            case self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_CANCELEDIT:
                return $this->defaultAccess();
                break;

            case self::BACKOFFICE_DOCUMENTFORMBACKOFFICE_DELETE:
                return $this->canDelete();
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


    /**
     * @param $subject
     * @return bool
     */
    protected function canEdit($subject)
    {
        if (!$subject instanceof Document) {
            return FALSE;
        }

        /* @var $document \EWW\Dpf\Domain\Model\Document */
        $document = $subject;

        if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {

            if (
                $document->getLocalStatus() === LocalDocumentStatus::NEW &&
                $document->getOwner() !== $this->security->getUser()->getUid()
            ) {
                return FALSE;
            }

            return TRUE;
        }

        if ($this->security->getUserRole() === Security::ROLE_RESEARCHER) {

            if ($document->getOwner() !== $this->security->getUser()->getUid()) {
                return FALSE;
            }

            if (
                $document->getLocalStatus() === LocalDocumentStatus::NEW ||
                $document->getLocalStatus() === LocalDocumentStatus::REGISTERED
            ) {
                return TRUE;
            }

            return FALSE;
        }

    }

    /**
     * @param $subject
     * @return bool
     */
    protected function canUpdate($subject)
    {
       return $this->canEdit($subject);
    }

    /**
     * @param $subject
     * @return bool
     */
    protected function canDelete($subject)
    {
        if (!$subject instanceof Document) {
            return false;
        }

        /* @var $document \EWW\Dpf\Domain\Model\Document */
        $document = $subject;

        return FALSE;
    }

}