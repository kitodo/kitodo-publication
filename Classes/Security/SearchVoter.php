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

class SearchVoter extends Voter
{
    const BACKOFFICE_SEARCH_LIST = "BACKOFFICE_SEARCH_LIST";
    const BACKOFFICE_SEARCH_SEARCH = "BACKOFFICE_SEARCH_SEARCH";
    const BACKOFFICE_SEARCH_UPDATEINDEX = "BACKOFFICE_SEARCH_UPDATEINDEX";
    const BACKOFFICE_SEARCH_DOUBLETCHECK = "BACKOFFICE_SEARCH_DOUBLETCHECK";
    const BACKOFFICE_SEARCH_IMPORT = "BACKOFFICE_SEARCH_IMPORT";
    const BACKOFFICE_SEARCH_LATEST = "BACKOFFICE_SEARCH_LATEST";
    const BACKOFFICE_SEARCH_EXTENDEDSEARCH = "BACKOFFICE_SEARCH_EXTENDEDSEARCH";

    /**
     * DocumentFormBackofficeVoter constructor.
     */
    public function __construct()
    {
        $this->attributes = array(
            self::BACKOFFICE_SEARCH_DOUBLETCHECK,
            self::BACKOFFICE_SEARCH_LIST,
            self::BACKOFFICE_SEARCH_SEARCH,
            self::BACKOFFICE_SEARCH_IMPORT,
            self::BACKOFFICE_SEARCH_UPDATEINDEX,
            self::BACKOFFICE_SEARCH_EXTENDEDSEARCH,
            self::BACKOFFICE_SEARCH_LATEST
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

            case self::BACKOFFICE_SEARCH_LIST:
                return $this->defaultAccess();
                break;
            case self::BACKOFFICE_SEARCH_EXTENDEDSEARCH:
            case self::BACKOFFICE_SEARCH_LATEST:
            case self::BACKOFFICE_SEARCH_SEARCH:
                return $this->defaultAccess();
                break;
            case self::BACKOFFICE_SEARCH_DOUBLETCHECK:
                return $this->librarianOnly();
                break;
            case self::BACKOFFICE_SEARCH_IMPORT:
                return $this->librarianOnly();
                break;
            case self::BACKOFFICE_SEARCH_UPDATEINDEX:
                return $this->librarianOnly();
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
     * @return bool
     */
    protected function librarianOnly()
    {
        if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {
            return TRUE;
        }

        return FALSE;
    }

}