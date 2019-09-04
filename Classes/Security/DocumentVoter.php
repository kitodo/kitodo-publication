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

class DocumentVoter extends Voter
{
    const BACKOFFICE_DOCUMENT_LIST = "BACKOFFICE_DOCUMENT_LIST";
    const BACKOFFICE_DOCUMENT_LISTREGISTERED = "BACKOFFICE_DOCUMENT_LISTREGISTERED";
    const BACKOFFICE_DOCUMENT_LISTINPROGRESS = "BACKOFFICE_DOCUMENT_LISTINPROGRESS";
    const BACKOFFICE_DOCUMENT_DISCARD = "BACKOFFICE_DOCUMENT_DISCARD";
    const BACKOFFICE_DOCUMENT_DELETELOCALLY = "BACKOFFICE_DOCUMENT_DELETELOCALLY";
    const BACKOFFICE_DOCUMENT_DUPLICATE = "BACKOFFICE_DOCUMENT_DUPLICATE";
    const BACKOFFICE_DOCUMENT_RELEASE = "BACKOFFICE_DOCUMENT_RELEASE";
    const BACKOFFICE_DOCUMENT_RESTORE = "BACKOFFICE_DOCUMENT_RESTORE";
    const BACKOFFICE_DOCUMENT_DELETE = "BACKOFFICE_DOCUMENT_DELETE";
    const BACKOFFICE_DOCUMENT_ACTIVATE = "BACKOFFICE_DOCUMENT_ACTIVATE";
    const BACKOFFICE_DOCUMENT_REGISTER = "BACKOFFICE_DOCUMENT_REGISTER";
    const BACKOFFICE_DOCUMENT_SHOWDETAILS = "BACKOFFICE_DOCUMENT_SHOWDETAILS";
    const BACKOFFICE_DOCUMENT_CANCELLISTTASK = "BACKOFFICE_DOCUMENT_CANCELLISTTASK";
    const BACKOFFICE_DOCUMENT_INACTIVATE = "BACKOFFICE_DOCUMENT_INACTIVATE";
    const BACKOFFICE_DOCUMENT_UPLOADFILES = "BACKOFFICE_DOCUMENT_UPLOADFILES";

    public function __construct()
    {
        $this->attributes = array(
            self::BACKOFFICE_DOCUMENT_LIST,
            self::BACKOFFICE_DOCUMENT_LISTREGISTERED,
            self::BACKOFFICE_DOCUMENT_LISTINPROGRESS,
            self::BACKOFFICE_DOCUMENT_DISCARD,
            self::BACKOFFICE_DOCUMENT_DELETELOCALLY,
            self::BACKOFFICE_DOCUMENT_DUPLICATE,
            self::BACKOFFICE_DOCUMENT_RELEASE,
            self::BACKOFFICE_DOCUMENT_RESTORE,
            self::BACKOFFICE_DOCUMENT_DELETE,
            self::BACKOFFICE_DOCUMENT_ACTIVATE,
            self::BACKOFFICE_DOCUMENT_REGISTER,
            self::BACKOFFICE_DOCUMENT_SHOWDETAILS,
            self::BACKOFFICE_DOCUMENT_CANCELLISTTASK,
            self::BACKOFFICE_DOCUMENT_INACTIVATE,
            self::BACKOFFICE_DOCUMENT_UPLOADFILES
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

        if (!$subject instanceof Document && !is_null($subject)) {
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
            case self::BACKOFFICE_DOCUMENT_LIST:
                return $this->defaultAccess();
                break;

            case self::BACKOFFICE_DOCUMENT_LISTREGISTERED:
                return $this->defaultAccess();
                break;

            case self::BACKOFFICE_DOCUMENT_LISTINPROGRESS:
                return $this->defaultAccess();
                break;

            case self::BACKOFFICE_DOCUMENT_DISCARD:
                return $this->canDiscard($subject);
                break;

            case self::BACKOFFICE_DOCUMENT_DELETELOCALLY:
                return $this->canDeleteLocally($subject);
                break;

            case self::BACKOFFICE_DOCUMENT_DUPLICATE:
                return $this->librarianOnly($subject);
                break;

            case self::BACKOFFICE_DOCUMENT_RELEASE:
                return $this->librarianOnly($subject);
                break;

            case self::BACKOFFICE_DOCUMENT_RESTORE:
                return $this->librarianOnly($subject);
                break;

            case self::BACKOFFICE_DOCUMENT_DELETE:
                return $this->canDelete($subject);
                break;

            case self::BACKOFFICE_DOCUMENT_ACTIVATE:
                return $this->canChangeRemoteStatus($subject);
                break;

            case self::BACKOFFICE_DOCUMENT_REGISTER:
                return $this->canRegister($subject);
                break;

            case self::BACKOFFICE_DOCUMENT_SHOWDETAILS:
                return $this->canShowDetails($subject);
                break;

            case self::BACKOFFICE_DOCUMENT_CANCELLISTTASK:
                return $this->defaultAccess();
                break;

            case self::BACKOFFICE_DOCUMENT_INACTIVATE:
                return $this->canChangeRemoteStatus($subject);
                break;

            case self::BACKOFFICE_DOCUMENT_UPLOADFILES:
                return $this->canUpload($subject);
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
    protected function librarianOnly()
    {
        if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * @param object $subject
     * @return bool
     */
    protected function canDiscard($subject)
    {
        if (!$subject instanceof Document) {
            return FALSE;
        }

        /* @var $document \EWW\Dpf\Domain\Model\Document */
        $document = $subject;

        if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {
            return TRUE;
        }

        if ($this->security->getUserRole() === Security::ROLE_RESEARCHER) {
            if ($document->getOwner() === $this->security->getUser()->getUid()) {
                return $document->getLocalStatus() === LocalDocumentStatus::REGISTERED;
            }
        }

        return FALSE;
    }

    /**
     *
     * @param object $subject
     * @return bool
     */
    protected function canDelete($subject)
    {
        if (!$subject instanceof Document) {
            return FALSE;
        }

        if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * @param object $subject
     * @return bool
     */
    protected function canShowDetails($subject)
    {
        if (!$subject instanceof Document) {
            return FALSE;
        }

        /* @var $document \EWW\Dpf\Domain\Model\Document */
        $document = $subject;

        if ($document->getOwner() === $this->security->getUser()->getUid()) {
            return TRUE;
        }

        if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {

            if ($document->getLocalStatus() === LocalDocumentStatus::NEW) {
                return FALSE;
            }

            return TRUE;
        }

        if ($this->security->getUserRole() === Security::ROLE_RESEARCHER) {

            if ($document->getLocalStatus() === LocalDocumentStatus::NEW) {
                return FALSE;
            }

            return TRUE;
        }

        return FALSE;
    }

    /**
     * @param object $subject
     * @return bool
     */
    protected function canUpload($subject)
    {
        if (!$subject instanceof Document) {
            return FALSE;
        }

        if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {
            return TRUE;
        }

        if ($this->security->getUserRole() === Security::ROLE_RESEARCHER) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * @param object $subject
     * @return bool
     */
    protected function canChangeRemoteStatus($subject)
    {
        if (!$subject instanceof Document) {
            return FALSE;
        }

        if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * @param object $subject
     * @return bool
     */
    protected function canRegister($subject)
    {
        if (!$subject instanceof Document) {
            return FALSE;
        }

        /* @var $document \EWW\Dpf\Domain\Model\Document */
        $document = $subject;

        if ($document->getOwner() === $this->security->getUser()->getUid()) {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * @param $subject
     * @return bool
     */
    protected function canDeleteLocally($subject)
    {
        if (!$subject instanceof Document) {
            return false;
        }

        /* @var $document \EWW\Dpf\Domain\Model\Document */
        $document = $subject;

        if (
            $document->getLocalStatus() === LocalDocumentStatus::NEW &&
            $document->getOwner() === $this->security->getUser()->getUid()
        ) {
            return TRUE;
        }

        return FALSE;
    }

}