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

use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Model\FrontendUser;
use EWW\Dpf\Domain\Model\MetadataGroup;
use EWW\Dpf\Services\FeUser\FisDataService;
use EWW\Dpf\Services\FeUser\GndDataService;

use EWW\Dpf\Services\FeUser\OrcidDataService;
use EWW\Dpf\Services\FeUser\RorDataService;
use EWW\Dpf\Services\FeUser\UnpaywallDataService;
use EWW\Dpf\Services\FeUser\ZdbDataService;
use EWW\Dpf\Session\BulkImportSessionData;
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
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $bookmarkRepository = null;

    /**
     * frontendUserRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FrontendUserRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $frontendUserRepository = null;

    /**
     * metadataGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $metadataGroupRepository = null;

    /**
     * externalMetadataRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ExternalMetadataRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $externalMetadataRepository = null;

    /**
     * documentManager
     *
     * @var \EWW\Dpf\Services\Document\DocumentManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentManager = null;

    /**
     * editingLockService
     *
     * @var \EWW\Dpf\Services\Document\EditingLockService
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $editingLockService = null;

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
     * @param string $type
     * @return false|string
     */
    public function searchFisAction($searchTerm, $type = 'person') {
        $fisUserDataService = new FisDataService();
        $methodName = 'search'.ucfirst($type).'Request';
        $result = $fisUserDataService->{$methodName}($searchTerm);

        return json_encode($result);
    }

    /**
     * @param string $dataId
     * @param int $groupId
     * @param int $groupIndex
     * @param int $fieldIndex
     * @param int $pageId
     * @param string $type
     * @return false|string
     */
    public function getFisDataAction($dataId, $groupId, $groupIndex, $fieldIndex, $pageId, $type = 'person') {
        $fisDataService = new FisDataService();
        $methodName = 'get'.ucfirst($type).'Data';
        $fisData = $fisDataService->{$methodName}($dataId);

        $result = $this->getApiMappingArray($groupId, $fisData, $groupIndex, $fieldIndex, $pageId, 'getFis'.ucfirst($type).'Mapping');

        return json_encode($result);
    }

    /**
     * @param string $searchTerm
     * @param string $type
     * @return false|string
     */
    public function searchGndAction($searchTerm, $type = 'person') {
        $gndUserDataService = new GndDataService();
        $methodName = 'search'.ucfirst($type).'Request';
        $result = $gndUserDataService->{$methodName}($searchTerm);

        return json_encode($result);
    }

    /**
     * @param string $dataId
     * @param int $groupId
     * @param int $groupIndex
     * @param int $fieldIndex
     * @param int $pageId
     * @param string $type
     */
    public function getGndDataAction($dataId, $groupId, $groupIndex, $fieldIndex, $pageId, $type = 'person') {
        $gndUserDataService = new GndDataService();
        $methodName = 'get'.ucfirst($type).'Data';
        $gndData = $gndUserDataService->{$methodName}($dataId);

        $result = $this->getApiMappingArray($groupId, $gndData, $groupIndex, $fieldIndex, $pageId, 'getGnd'.ucfirst($type).'Mapping');

        return json_encode($result);
    }

    /**
     * search ROR API
     * API is organisation only!
     * @param string $searchTerm
     * @return false|string
     */
    public function searchRorAction($searchTerm) {
        $rorUserDataService = new RorDataService();
        $result = $rorUserDataService->searchOrganisationRequest($searchTerm);

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
        $rorData = $rorUserDataService->getOrganisationData($dataId);

        $result = $this->getApiMappingArray($groupId, $rorData, $groupIndex, $fieldIndex, $pageId, 'getRorMapping');

        return json_encode($result);
    }

    /**
     * @param string $searchTerm
     * @return false|string
     */
    public function searchZdbAction($searchTerm) {
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
     * @param string $searchTerm
     */
    public function searchUnpaywallAction($searchTerm) {
        $unpaywallUserDataService = new UnpaywallDataService();
        $result = $unpaywallUserDataService->searchRequest($searchTerm);

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
    public function getUnpaywallDataAction($dataId, $groupId, $groupIndex, $fieldIndex, $pageId) {
        $unpaywallDataService = new UnpaywallDataService();
        $unpaywallData = $unpaywallDataService->getDataRequest($dataId);

        $result = $this->getApiMappingArray($groupId, $unpaywallData, $groupIndex, $fieldIndex, $pageId, 'getUnpaywallMapping');

        return json_encode($result);
    }

    /**
     * @param string $searchTerm
     * @param string $type
     */
    public function searchOrcidAction($searchTerm, $type = 'person') {
        $orcidUserDataService = new OrcidDataService();
        $methodName = 'search'.ucfirst($type).'Request';
        $result = $orcidUserDataService->{$methodName}($searchTerm);

        return json_encode($result);
    }

    /**
     * @param string $dataId
     * @param int $groupId
     * @param int $groupIndex
     * @param int $fieldIndex
     * @param int $pageId
     * @param string $type
     * @return false|string
     */
    public function getOrcidDataAction($dataId, $groupId, $groupIndex, $fieldIndex, $pageId, $type = 'person') {
        $orcidDataService = new OrcidDataService();
        $methodName = 'get'.ucfirst($type).'Data';
        $orcidData = $orcidDataService->{$methodName}($dataId);

        $result = $this->getApiMappingArray($groupId, $orcidData, $groupIndex, $fieldIndex, $pageId, 'getOrcid'.ucfirst($type).'Mapping');

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
                // for configuration field1->field1a
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


    /**
     * Selects or unselects an external metadata record to be imported later as a document.
     *
     * @param string $identifier
     * @return bool
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function toggleBulkImportRecordAction($identifier)
    {
        $externalMetaData = $this->externalMetadataRepository->findOneByPublicationIdentifier($identifier);

        if ($externalMetaData) {
            $this->externalMetadataRepository->remove($externalMetaData);
        } else {
            /** @var BulkImportSessionData $bulkImportSessionData */
            $bulkImportSessionData = $this->session->getBulkImportData();
            $currentResults = $bulkImportSessionData->getCurrentMetadataItems();
            if ($currentResults && is_array($currentResults)) {
                $this->externalMetadataRepository->add($currentResults[$identifier]);
            }
        }

        return true;
    }

    /**
     * Activates/deactivates the author search for the bulk import.
     *
     * @param string $apiName
     * @return bool
     */
    public function toggleBulkImportAuthorSearchAction($apiName)
    {
        /** @var BulkImportSessionData $bulkImportData */
        $bulkImportSessionData = $this->session->getBulkImportData();

        switch ($apiName) {
            case 'CrossRef':
                $searchField = $bulkImportSessionData->getCrossRefSearchField();
                if ($searchField === 'author') {
                    $searchField = '';
                } else {
                    $searchField = 'author';
                }
                $bulkImportSessionData->setCrossRefSearchField($searchField);
                break;
            case 'PubMed':
                $searchField = $bulkImportSessionData->getPubMedSearchField();
                if ($searchField === 'author') {
                    $searchField = '';
                } else {
                    $searchField = 'author';
                }
                $bulkImportSessionData->setPubMedSearchField($searchField);
                break;
            default:
                return false;
        }

        $this->session->setBulkImportData($bulkImportSessionData);

        return true;
    }

    public function initializeAction()
    {
        $this->authorizationChecker->denyAccessUnlessLoggedIn();

        parent::initializeAction();
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

    /**
     * @param string $identifier
     * @return bool
     */
    public function isDocumentEditableAction($identifier)
    {
        $document = $this->documentManager->read($identifier);
        $currentDocument = $this->session->getCurrenDocument();

        $currentUser = $this->security->getUser();

        if ( $this->editingLockService->isLocked($identifier, $currentUser->getUid()) ) {
            return json_encode(['allowed' => false, 'reason' => 'isLocked']);
        }

        if ($document instanceof Document && $currentDocument instanceof Document) {
            return json_encode(
                [
                    'allowed' => $currentDocument->getState() === $document->getState(),
                    'reason' => $currentDocument->getState() === $document->getState()? 'isOk' : 'hasChanged'
                ]
            );
        }

        return json_encode(['allowed' => false, 'reason' => 'unknown']);
    }

}
