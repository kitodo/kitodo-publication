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
     * storedSearches
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\StoredSearch>
     * @cascade remove
     */
    protected $storedSearches = null;

    /**
     * frontendUserGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FrontendUserGroupRepository
     * @inject
     */
    protected $frontendUserGroupRepository = null;

    /**
     * @var boolean
     */
    protected $notifyPersonalLink = 0;

    /**
     * @var boolean
     */
    protected $notifyStatusChange = 0;

    /**
     * @var boolean
     */
    protected $notifyFulltextPublished = 0;

    /**
     * @var boolean
     */
    protected $notifyNewPublicationMypublication = 0;

    /**
     * @var string
     */
    protected $fisPersId = '';

    /**
     * __construct
     */
    public function __construct()
    {
        parent::__construct();

        //Do not remove the next line: It would break the functionality
        $this->initStorageObjects();
    }

    /**
     * Initializes all ObjectStorage properties
     *
     * @return void
     */
    protected function initStorageObjects()
    {
        $this->storedSearches = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }


    /**
     * Returns the storedSearches
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\StoredSearch>
     */
    public function getStoredSearches()
    {
        return $this->storedSearches;
    }

    /**
     * Sets the storedSearches
     *
     * @param  \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\EWW\Dpf\Domain\Model\StoredSearch> $storedSearches
     * @return void
     */
    public function setStoredSearches(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $storedSearches)
    {
        $this->storedSearches = $storedSearches;
    }

    /**
     * @param \EWW\Dpf\Domain\Model\StoredSearch $storedSearch
     */
    public function addStoredSearch(\EWW\Dpf\Domain\Model\StoredSearch $storedSearch)
    {
        $this->storedSearches->attach($storedSearch);
    }

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

    /**
     * @return string
     */
    public function getFobIdentifier(): string
    {
        return '1234567890';
    }

    /**
     * @return bool
     */
    public function isNotifyPersonalLink(): bool
    {
        return $this->notifyPersonalLink;
    }

    /**
     * @param bool $notifyPersonalLink
     */
    public function setNotifyPersonalLink(bool $notifyPersonalLink)
    {
        $this->notifyPersonalLink = $notifyPersonalLink;
    }

    /**
     * @return bool
     */
    public function isNotifyStatusChange(): bool
    {
        return $this->notifyStatusChange;
    }

    /**
     * @param bool $notifyStatusChange
     */
    public function setNotifyStatusChange(bool $notifyStatusChange)
    {
        $this->notifyStatusChange = $notifyStatusChange;
    }

    /**
     * @return bool
     */
    public function isNotifyFulltextPublished(): bool
    {
        return $this->notifyFulltextPublished;
    }

    /**
     * @param bool $notifyFulltextPublished
     */
    public function setNotifyFulltextPublished(bool $notifyFulltextPublished)
    {
        $this->notifyFulltextPublished = $notifyFulltextPublished;
    }

    /**
     * @return bool
     */
    public function isNotifyNewPublicationMypublication(): bool
    {
        return $this->notifyNewPublicationMypublication;
    }

    /**
     * @param bool $notifyNewPublicationMypublication
     */
    public function setNotifyNewPublicationMypublication(bool $notifyNewPublicationMypublication)
    {
        $this->notifyNewPublicationMypublication = $notifyNewPublicationMypublication;
    }

    /**
     * @return string
     */
    public function getFisPersId(): string
    {return $this->getFirstName();

        return $this->fisPersId;
    }

}