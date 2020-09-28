<?php
namespace EWW\Dpf\Controller;

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

use EWW\Dpf\Domain\Model\FrontendUser;
use EWW\Dpf\Session\SearchSessionData;

/**
 * AjaxBackofficeController
 */
class AjaxBackofficeController extends \EWW\Dpf\Controller\AbstractController
{
    /**
     * bookmarkRepository
     *
     * @var \EWW\Dpf\Domain\Repository\BookmarkRepository
     * @inject
     */
    protected $bookmarkRepository = null;

    /**
     * frontendUserRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FrontendUserRepository
     * @inject
     */
    protected $frontendUserRepository = null;


    /**
     * Adds a the given document identifier to the bookmark list of the current fe user.
     *
     * @param string $identifier
     * @return bool
     */
    public function addBookmarkAction($identifier)
    {
        /** @var \EWW\Dpf\Domain\Model\Bookmark $bookmark */
        $bookmark = $this->bookmarkRepository->findBookmark($this->security->getUser()->getUid(), $identifier);
        if (!$bookmark) {
            $bookmark = $this->objectManager->get(\EWW\Dpf\Domain\Model\Bookmark::class);
            $bookmark->setDocumentIdentifier($identifier);
            $bookmark->setFeUserUid($this->security->getUser()->getUid());
            $this->bookmarkRepository->add($bookmark);
            return true;
        }

        return false;
    }

    /**
     * Removes the given document from the bookmark list of the current fe user.
     *
     * @param string $identifier
     * @return bool
     */
    public function removeBookmarkAction($identifier)
    {
        /** @var \EWW\Dpf\Domain\Model\Bookmark $bookmark */
        $bookmark = $this->bookmarkRepository->findBookmark($this->security->getUser()->getUid(), $identifier);
        if ($bookmark) {
            $this->bookmarkRepository->remove($bookmark);
            return true;
        }

        return false;
    }

    /**
     * Adds a workspace filter to the session.
     *
     * @param string $name
     * @param array $values
     * @return bool
     */
    public function addWorkspaceFilterAction($name, $values = [])
    {
        /** @var SearchSessionData $workspaceSessionData */
        $workspaceSessionData = $this->session->getWorkspaceData();
        $workspaceSessionData->setFilter($name, $values);
        $this->session->setWorkspaceData($workspaceSessionData);
        return true;
    }

    /**
     * Adds a workspace sort to the session.
     *
     * @param string $field
     * @param string $order
     * @return bool
     */
    public function addWorkspaceSortAction($field, $order)
    {
        /** @var SearchSessionData $workspaceSessionData */
        $workspaceSessionData = $this->session->getWorkspaceData();
        $workspaceSessionData->setSortField($field);
        $workspaceSessionData->setSortOrder($order);
        $this->session->setWorkspaceData($workspaceSessionData);
        return true;
    }

    /**
     * Toggles the filter to exclude discarded documents.
     *
     * @return bool
     */
    public function toggleWorkspaceExcludeDiscardedAction()
    {
        /** @var SearchSessionData $workspaceSessionData */
        $workspaceSessionData = $this->session->getWorkspaceData();
        $workspaceSessionData->toggleExcludeDiscardedFilter();
        $this->session->setWorkspaceData($workspaceSessionData);
        return true;
    }

    /**
     * Toggles the filter to hide bookmarked documents.
     *
     * @return bool
     */
    public function toggleWorkspaceBookmarksOnlyAction()
    {
        /** @var SearchSessionData $workspaceSessionData */
        $workspaceSessionData = $this->session->getWorkspaceData();
        $workspaceSessionData->toggleBookmarksOnlyFilter();
        $this->session->setWorkspaceData($workspaceSessionData);
        return true;
    }

    /**
     * Sets the items per page for the workspace list.
     *
     * @param int $itemsPerPage
     * @return bool
     */
    public function setWorkspaceItemsPerPageAction($itemsPerPage)
    {
        /** @var SearchSessionData $workspaceSessionData */
        $workspaceSessionData = $this->session->getWorkspaceData();
        $workspaceSessionData->setItemsPerPage($itemsPerPage);
        $this->session->setWorkspaceData($workspaceSessionData);
        return true;
    }


    /**
     * Save an extended search query.
     *
     * @param string $name
     * @param string $query
     * @return bool
     */
    public function saveExtendedSearchAction($name, $query)
    {
        $search = new \EWW\Dpf\Domain\Model\StoredSearch();
        $search->setName($name);
        $search->setQuery($query);

        /** @var FrontendUser $feUser */
        $feUser = $this->security->getUser();
        $feUser->addStoredSearch($search);
        $this->frontendUserRepository->update($feUser);

        return true;
    }

    /**
     * Loads a stored extended search query.
     *
     * @param int $id
     * @return bool
     */
    public function loadExtendedSearchAction($id)
    {
        /** @var FrontendUser $feUser */
        $feUser = $this->security->getUser();
        $searches = $feUser->getStoredSearches();

        foreach ($searches as $search) {
            if ($search->getUid() == $id) {
                return $search->getQuery();
            }
        }

        return false;
    }

    /**
     * Loads a list of all stored extended search queries.
     *
     * @return string
     */
    public function loadExtendedSearchListAction()
    {
        /** @var FrontendUser $feUser */
        $feUser = $this->security->getUser();

        $searches = [];
        foreach ($feUser->getStoredSearches() as $search) {
            $searches[] = [
                'uid' => $search->getUid(),
                'name' => $search->getName(),
                'query' => $search->getQuery()
            ];
        }

        return json_encode($searches);
    }

    /**
     * @param int $feUser
     * @return false|string
     */
    public function generateApiTokenAction($feUser) {
        $currentUser = $this->security->getUser();
        if ($currentUser->getUid() === $feUser) {
            $string = md5(substr(md5(time()), 0, 14)).date("Y-m-dH:i:s");
            $hash = hash('sha256', $string);

            $currentUser->setApiToken($hash);
            $this->frontendUserRepository->update($currentUser);

            return json_encode(['apiToken' => $hash]);
        } else {
            return json_encode(['failed' => 'wrong user id']);
        }
    }

    /**
     * @param int $feUser
     * @return bool
     */
    public function removeApiTokenAction($feUser) {
        $currentUser = $this->security->getUser();
        if ($currentUser->getUid() === $feUser) {
            $currentUser->setApiToken('');
            $this->frontendUserRepository->update($currentUser);

            return json_encode(['success' => '1']);
        } else {
            return json_encode(['failed' => 'wrong user id']);
        }

    }

}
