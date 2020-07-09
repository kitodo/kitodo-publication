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
use EWW\Dpf\Domain\Model\MetadataGroup;
use EWW\Dpf\Services\FeUser\FisDataService;
use EWW\Dpf\Services\FeUser\GndDataService;

use EWW\Dpf\Services\FeUser\RorDataService;
use EWW\Dpf\Services\FeUser\ZdbDataService;
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
     * metadataGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
     * @inject
     */
    protected $metadataGroupRepository = null;


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
     * @param string $searchTerm
     * @return false|string
     */
    public function searchFisUserAction($searchTerm) {
        $fisUserDataService = new FisDataService();
        $result = $fisUserDataService->searchPersonRequest($searchTerm);

        return json_encode($result);
    }

    /**
     * @param string $dataId
     * @param int $groupId
     * @param int $groupIndex
     * @param int $fieldIndex
     * @param int $pageId
     * @return false|string
     */
    public function getFisUserDataAction($dataId, $groupId, $groupIndex, $fieldIndex, $pageId) {
        $fisUserDataService = new FisDataService();
        $fisUserData = $fisUserDataService->getFisUserData($dataId);

        $result = $this->getApiMappingArray($groupId, $fisUserData, $groupIndex, $fieldIndex, $pageId, 'getFisMapping');

        return json_encode($result);
    }

    /**
     * @param string $searchTerm
     * @return false|string
     */
    public function searchGndDataAction($searchTerm) {
        $gndUserDataService = new GndDataService();
        $result = $gndUserDataService->searchRequest($searchTerm);

        return json_encode($result);
    }

    /**
     * @param string $dataId
     * @param int $groupId
     * @param int $groupIndex
     * @param int $fieldIndex
     * @param int $pageId
     */
    public function getGndDataAction($dataId, $groupId, $groupIndex, $fieldIndex, $pageId) {
        $gndUserDataService = new GndDataService();
        $gndData = $gndUserDataService->getData($dataId);

        $result = $this->getApiMappingArray($groupId, $gndData, $groupIndex, $fieldIndex, $pageId, 'getGndMapping');

        return json_encode($result);
    }

    /**
     * @param string $searchTerm
     * @return false|string
     */
    public function searchRorDataAction($searchTerm) {
        $rorUserDataService = new RorDataService();
        $result = $rorUserDataService->searchRequest($searchTerm);

        return json_encode($result);
    }

    /**
     * @param string $dataId
     * @param int $groupId
     * @param int $groupIndex
     * @param int $fieldIndex
     * @param int $pageId
     */
    public function getRorDataAction($dataId, $groupId, $groupIndex, $fieldIndex, $pageId) {
        $rorUserDataService = new RorDataService();
        $rorData = $rorUserDataService->getDataRequest($dataId);

        $result = $this->getApiMappingArray($groupId, $rorData, $groupIndex, $fieldIndex, $pageId, 'getRorMapping');

        return json_encode($result);
    }

    /**
     * @param string $searchTerm
     * @return false|string
     */
    public function searchZdbDataAction($searchTerm) {
        $zdbUserDataService = new ZdbDataService();
        $result = $zdbUserDataService->searchRequest($searchTerm);

        return json_encode($result);
    }

    /**
     * @param string $dataId
     * @param int $groupId
     * @param int $groupIndex
     * @param int $fieldIndex
     * @param int $pageId
     * @return false|string
     */
    public function getZdbDataAction($dataId, $groupId, $groupIndex, $fieldIndex, $pageId) {
        $zdbDataService = new ZdbDataService();
        $zdbData = $zdbDataService->getDataRequest($dataId);

        $result = $this->getApiMappingArray($groupId, $zdbData, $groupIndex, $fieldIndex, $pageId, 'getZdbMapping');

        return json_encode($result);
    }

    /**
     * Preparing data from api and returning an array to identify specific field in frontend
     * @param $groupId
     * @param $data
     * @param $groupIndex
     * @param $fieldIndex
     * @param $pageId
     * @param $methodMappingName
     * @return mixed
     */
    public function getApiMappingArray($groupId, $data, $groupIndex, $fieldIndex, $pageId, $methodMappingName) {
        // get mapping
        /** @var MetadataGroup $group */
        $group = $this->metadataGroupRepository->findByUid($groupId);

        foreach ($group->getChildren() as $key => $value) {
            if (!empty($value->{$methodMappingName}())) {
                $mappingPart = explode('->', $value->{$methodMappingName}());
                $apiData = '';
                $i = 0;
                foreach ($mappingPart as $mapping) {
                    if ($i == 0) {
                        $apiData = $data->{$mapping};
                    } else {
                        if (is_array($apiData)) {
                            foreach ($apiData as $fisArrayValue) {
                                $apiDataArray[] = $fisArrayValue->{$mapping};
                            }
                        } else {
                            $apiData = $apiData->{$mapping};
                        }
                    }
                    $i++;
                }

                if (!empty($apiData) || !empty($apiDataArray)) {
                    if (!empty($apiDataArray)) {
                        foreach ($apiDataArray as $key => $apiDataValue) {
                            $result[$pageId . '-' . $groupId . '-' . $groupIndex . '-' . $value->getUid() . '-' . $key] = $apiDataValue;
                        }
                        $apiDataArray = [];
                    } else {
                        $result[$pageId . '-' . $groupId . '-' . $groupIndex . '-' . $value->getUid() . '-' . $fieldIndex] = $apiData;
                    }
                }
            }
        }

        return $result;
    }


}
