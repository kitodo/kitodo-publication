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

use EWW\Dpf\Services\ElasticSearch\ElasticSearch;
use EWW\Dpf\Exceptions\DPFExceptionInterface;
use EWW\Dpf\Security\DocumentVoter;
use EWW\Dpf\Security\Security;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use EWW\Dpf\Session\SearchSessionData;

/**
 * SearchController
 */
class SearchController extends \EWW\Dpf\Controller\AbstractController
{

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentRepository = null;

    /**
     * documenTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentTypeRepository;

    /**
     * clientRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ClientRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $clientRepository = null;


    /**
     * elasticSearch
     *
     * @var \EWW\Dpf\Services\ElasticSearch\ElasticSearch
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $elasticSearch = null;


    /**
     * queryBuilder
     *
     * @var \EWW\Dpf\Services\ElasticSearch\QueryBuilder
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $queryBuilder = null;


    /**
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $persistenceManager;


    /**
     * bookmarkRepository
     *
     * @var \EWW\Dpf\Domain\Repository\BookmarkRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $bookmarkRepository = null;

    /**
     * fisDataService
     *
     * @var \EWW\Dpf\Services\FeUser\FisDataService
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $fisDataService = null;

    /**
     * metadataGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $metadataGroupRepository;

    /**
     * documentStorage
     *
     * @var \EWW\Dpf\Services\Storage\DocumentStorage
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentStorage = null;

    const RESULT_COUNT      = 500;
    const NEXT_RESULT_COUNT = 500;

    /**
     * list
     *
     * @param int $from
     * @param int $queryString
     *
     * @return void
     */
    protected function list($from = 0, $queryString = '')
    {
        /** @var SearchSessionData $workspaceSessionData */
        $workspaceSessionData = $this->session->getWorkspaceData();
        $filters = $workspaceSessionData->getFilters();
        $excludeFilters = $workspaceSessionData->getExcludeFilters();

        if (array_key_exists('bookmarks', $excludeFilters)) {
            unset($excludeFilters['bookmarks']);
        }

        $sortField = $workspaceSessionData->getSortField();
        $sortOrder = $workspaceSessionData->getSortOrder();

        if ($this->security->getUserRole() == Security::ROLE_LIBRARIAN) {
            $query = $this->getSearchQuery($from, [],
                $filters, $excludeFilters, $sortField, $sortOrder, $queryString);
        } elseif ($this->security->getUserRole() == Security::ROLE_RESEARCHER) {
            $query = $this->getSearchQuery($from, [],
                $filters, $excludeFilters, $sortField, $sortOrder, $queryString);
        }

        try {
            $results = $this->elasticSearch->search($query, 'object');
        } catch (\Exception $e) {
            $workspaceSessionData->clearSort();
            $workspaceSessionData->clearFilters();
            $this->session->setWorkspaceData($workspaceSessionData);

            $this->addFlashMessage(
                "Error while building the list!", '', AbstractMessage::ERROR
            );
        }

        if ($this->request->hasArgument('message')) {
            $this->view->assign('message', $this->request->getArgument('message'));
        }

        if ($this->request->hasArgument('errorFiles')) {
            $this->view->assign('errorFiles', $this->request->getArgument('errorFiles'));
        }

        if ($filters && $results['hits']['total']['value'] < 1) {
            $workspaceSessionData->clearSort();
            $workspaceSessionData->clearFilters();
            $this->session->setWorkspaceData($workspaceSessionData);
            list($redirectAction, $redirectController) = $this->session->getStoredAction();
            $this->redirect(
                $redirectAction, $redirectController, null,
                array('message' => [], 'checkedDocumentIdentifiers' => [])
            );
        }

        $this->view->assign('documentCount', $results['hits']['total']['value']);
        $this->view->assign('documents', $results['hits']['hits']);
        $this->view->assign('pages', range(1, $results['hits']['total']['value']));
        $this->view->assign('itemsPerPage', $this->itemsPerPage());
        $this->view->assign('aggregations', $results['aggregations']);
        $this->view->assign('filters', $filters);
        $this->view->assign('isHideDiscarded', array_key_exists('aliasState', $excludeFilters));
        $this->view->assign('isBookmarksOnly', array_key_exists('bookmarks', $excludeFilters));
        $this->view->assign('bookmarkIdentifiers', []);

        if ($this->fisDataService->getPersonData($this->security->getFisPersId())) {
            $this->view->assign('currentFisPersId', $this->security->getFisPersId());
        }

        try {
            $personGroup = $this->metadataGroupRepository->findPersonGroup();
            $this->view->assign('personGroup', $personGroup->getUid());
        } catch (\Throwable $e) {
            $this->addFlashMessage(
                "Missing configuration: Person group.", '', AbstractMessage::ERROR
            );
        }
    }

    /**
     * action list
     *
     * @return void
     */
    public function searchAction()
    {
        $args = $this->request->getArguments();

        /** @var SearchSessionData $workspaceSessionData */
        $workspaceSessionData = $this->session->getWorkspaceData();

        if ($args['query'] && array_key_exists('fulltext', $args['query'])) {
            $queryString = $args['query']['fulltext'];
            $workspaceSessionData->setSimpleQuery($queryString);
        }

        if ($args['refresh']) {
            $workspaceSessionData->clearSort();
            $workspaceSessionData->clearFilters();
            $workspaceSessionData->setSimpleQuery("");
        }
        $this->session->setWorkspaceData($workspaceSessionData);

        $simpleSearch = $workspaceSessionData->getSimpleQuery();

        $this->session->setStoredAction($this->getCurrentAction(), $this->getCurrentController(),
            $this->uriBuilder->getRequest()->getRequestUri()
        );

        $currentPage = null;
        $checkedDocumentIdentifiers = [];
        $pagination = $this->getParametersSafely('@widget_0');
        if ($pagination) {
            $checkedDocumentIdentifiers = [];
            $currentPage = $pagination['currentPage'];
        } else {
            $currentPage = 1;
        }

        $this->list(
            (empty($currentPage)? 0 : ($currentPage-1) * $this->itemsPerPage()),
            $this->escapeQuery(trim($simpleSearch))
        );

        $this->view->assign('simpleSearch', $simpleSearch);
        $this->view->assign('currentPage', $currentPage);
        $this->view->assign('workspaceListAction', $this->getCurrentAction());
        $this->view->assign('checkedDocumentIdentifiers', $checkedDocumentIdentifiers);
    }


    /**
     * get list view data for the workspace
     *
     * @param int $from
     * @param array $bookmarkIdentifiers
     * @param array $filters
     * @param array $excludeFilters
     * @param string $sortField
     * @param string $sortOrder
     * @param string $queryString
     *
     * @return array
     */
    protected function getSearchQuery(
        $from = 0, $bookmarkIdentifiers = [], $filters= [], $excludeFilters = [],
        $sortField = null, $sortOrder = null, $queryString = null
    )
    {
        $workspaceFilter = [
            'bool' => [
                'must' => [
                    [
                        'bool' => [
                            'should' => [
                                [
                                    'term' => [
                                        'creator' => $this->security->getUser()->getUid()
                                    ]
                                ],
                                [
                                    'bool' => [
                                        'must_not' => [
                                            [
                                                'term' => [
                                                    'state' => DocumentWorkflow::STATE_NEW_NONE
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $this->queryBuilder->buildQuery(
            $this->itemsPerPage(), $workspaceFilter, $from, $bookmarkIdentifiers, $filters,
            $excludeFilters, $sortField, $sortOrder, $queryString
        );
    }


    /**
     * Batch operations action.
     * @param array $listData
     */
    public function batchAction($listData)
    {
        if (array_key_exists('action', $listData)) {
            $this->forward($listData['action'], null, null, ['listData' => $listData]);
        }
    }


    /**
     * Batch operation, bookmark documents.
     * @param array $listData
     */
    public function batchBookmarkAction($listData)
    {
        $successful = [];
        $checkedDocumentIdentifiers = [];

        if (array_key_exists('documentIdentifiers', $listData) && is_array($listData['documentIdentifiers']) ) {
            $checkedDocumentIdentifiers = $listData['documentIdentifiers'];

            foreach ($listData['documentIdentifiers'] as $documentIdentifier) {

                if ( $listData['documentAliasState'][$documentIdentifier] != DocumentWorkflow::ALIAS_STATE_NEW) {
                    if (
                        $this->bookmarkRepository->addBookmark(
                            $documentIdentifier,
                            $this->security->getUser()->getUid()
                        )
                    ) {
                        $successful[] = $documentIdentifier;
                    }
                }
            }

            if (sizeof($successful) == 1) {
                $locallangKey = 'manager.workspace.batchAction.bookmark.success.singular';
            } else {
                $locallangKey = 'manager.workspace.batchAction.bookmark.success.plural';
            }

            $message = LocalizationUtility::translate(
                $locallangKey,
                'dpf',
                [sizeof($successful), sizeof($listData['documentIdentifiers'])]
            );
            $this->addFlashMessage(
                $message, '',
                (sizeof($successful) > 0 ? AbstractMessage::OK : AbstractMessage::WARNING)
            );

        } else {
            $message = LocalizationUtility::translate(
                'manager.workspace.batchAction.failure',
                'dpf');
            $this->addFlashMessage($message, '', AbstractMessage::ERROR);
        }

        list($redirectAction, $redirectController) = $this->session->getStoredAction();
        $this->redirect(
            $redirectAction, $redirectController, null,
            array('message' => $message, 'checkedDocumentIdentifiers' =>  $checkedDocumentIdentifiers));
    }

        /**
     * extended search action
     */
    public function extendedSearchAction()
    {
        /** @var FrontendUser $feUser */
        $feUser = $this->security->getUser();

        $args = $this->request->getArguments();

        /** @var SearchSessionData $workspaceSessionData */
        $workspaceSessionData = $this->session->getWorkspaceData();

        if ($args['query'] && array_key_exists('fulltext', $args['query'])) {
            $queryString = $args['query']['fulltext'];
            $workspaceSessionData->setSimpleQuery($queryString);
        }

        if ($args['refresh']) {
            $workspaceSessionData->clearSort();
            $workspaceSessionData->clearFilters();
            $workspaceSessionData->setSimpleQuery("");
        }
        $this->session->setWorkspaceData($workspaceSessionData);

        $simpleSearch = $workspaceSessionData->getSimpleQuery();

        $documentTypes = $this->documentTypeRepository->findAll();

        $docTypes = [];
        foreach ($documentTypes as $documentType) {
            $docTypes[$documentType->getName()] = $documentType->getDisplayName();
        }
        asort($docTypes, SORT_LOCALE_STRING);
        $this->view->assign('documentTypes', $docTypes);

        $states[DocumentWorkflow::ALIAS_STATE_NEW] = LocalizationUtility::translate(
            "manager.documentList.state.".DocumentWorkflow::ALIAS_STATE_NEW, 'dpf'
        );
        $states[DocumentWorkflow::ALIAS_STATE_REGISTERED] = LocalizationUtility::translate(
            "manager.documentList.state.".DocumentWorkflow::ALIAS_STATE_REGISTERED, 'dpf'
        );
        $states[DocumentWorkflow::ALIAS_STATE_IN_PROGRESS] = LocalizationUtility::translate(
            "manager.documentList.state.".DocumentWorkflow::ALIAS_STATE_IN_PROGRESS, 'dpf'
        );
        $states[DocumentWorkflow::ALIAS_STATE_RELEASED] = LocalizationUtility::translate(
            "manager.documentList.state.".DocumentWorkflow::ALIAS_STATE_RELEASED, 'dpf'
        );
        $states[DocumentWorkflow::ALIAS_STATE_POSTPONED] = LocalizationUtility::translate(
            "manager.documentList.state.".DocumentWorkflow::ALIAS_STATE_POSTPONED, 'dpf'
        );
        $states[DocumentWorkflow::ALIAS_STATE_DISCARDED] = LocalizationUtility::translate(
            "manager.documentList.state.".DocumentWorkflow::ALIAS_STATE_DISCARDED, 'dpf'
        );

        $this->view->assign('states', $states);

        $this->session->setStoredAction($this->getCurrentAction(), $this->getCurrentController(),
            $this->uriBuilder->getRequest()->getRequestUri()
        );

        $currentPage = null;
        $checkedDocumentIdentifiers = [];
        $pagination = $this->getParametersSafely('@widget_0');
        if ($pagination) {
            $checkedDocumentIdentifiers = [];
            $currentPage = $pagination['currentPage'];
        } else {
            $currentPage = 1;
        }

        $this->list((empty($currentPage)? 0 : ($currentPage-1) * $this->itemsPerPage()), $simpleSearch);

        $this->view->assign('simpleSearch', $simpleSearch);
        $this->view->assign('currentPage', $currentPage);
        $this->view->assign('workspaceListAction', $this->getCurrentAction());
        $this->view->assign('checkedDocumentIdentifiers', $checkedDocumentIdentifiers);
    }

    /**
     * gets a list of latest documents
     */
    public function latestAction()
    {
        try {
            $query = $this->searchLatest();

            // set type local vs object
            $type = 'object';

            $results = $this->getResultList($query, $type);
        } catch (\Exception $exception) {
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }

            $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf');

            $this->addFlashMessage(
                $message,
                '',
                $severity,
                true
            );
        }

        if ($extSearch) {
            // redirect to extended search view
            $this->forward("extendedSearch", null, null, array('results' => $results, 'query' => $args['query']));
        } else {
            // redirect to list view
            $this->forward("list", null, null, array('results' => $results, 'query' => $args['query']));
        }
    }

    /**
     * action import
     *
     * @param  string $documentObjectIdentifier
     * @param  string $objectState
     * @return void
     */
    public function importAction($documentObjectIdentifier, $objectState)
    {
        $args = array();

        try {
            if ($this->documentStorage->retrieve($documentObjectIdentifier)) {
                $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_retrieve.success';
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
                $document = $this->documentRepository->findOneByObjectIdentifier($documentObjectIdentifier);
                $args[] = $document->getObjectIdentifier()." (".$document->getTitle().")";
            } else {
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.retrieve_failed';
            }
        } catch (\Exception $exception) {
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }
        }

        // Show success or failure of the action in a flash message

        $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf', $args);

        $this->addFlashMessage(
            $message,
            '',
            $severity,
            true
        );

        $this->forward('updateIndex', null, null, array('documentObjectIdentifier' => $documentObjectIdentifier));
    }

    /**
     *
     * @param  string $documentObjectIdentifier
     * @return void
     */
    public function updateIndexAction($documentObjectIdentifier)
    {
        $document = $this->documentRepository->findByObjectIdentifier($documentObjectIdentifier);

        if (is_a($document, Document::class)) {
            $elasticsearchRepository = $this->objectManager->get(ElasticsearchRepository::class);
            $elasticsearchMapper     = $this->objectManager->get(ElasticsearchMapper::class);
            $json                    = $elasticsearchMapper->getElasticsearchJson($document);
            // send document to index
            $elasticsearchRepository->add($document, $json);
        }

        $this->redirect('search');
    }


    /**
     * action doubletCheck
     *
     * @param  \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function doubletCheckAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $this->authorizationChecker->denyAccessUnlessGranted(DocumentVoter::DOUBLET_CHECK, $document);

        try {
            $elasticSearch = $this->objectManager->get(ElasticSearch::class);

            $client = $this->clientRepository->findAll()->current();

            // es source fields
            // title
            // abstract
            // author
            // language
            // publisher
            // publisher_place
            // distributor
            // distributor_place
            // distributor_date
            // classification
            // tag
            // identifier
            // submitter
            // project

            // is doublet existing?
            $query['body']['query']['bool']['must'][]['match']['title'] = $document->getTitle();

            // set owner id
            $query['body']['query']['bool']['must'][]['term']['OWNER_ID'] = $client->getOwnerId();

            $results = $elasticSearch->search($query, '');

            $searchList = array();

            // filter out identical document from the search result list
            foreach ($results['hits'] as $entry) {

                if ($document->getObjectIdentifier() && ($document->getObjectIdentifier() === $entry['_source']['PID'])) {
                    continue;
                }

                $entryIdentifier = $entry['_source']['_dissemination']['_content']['identifier'][0];
                if (is_numeric($entryIdentifier) && $document->getUid() == $entryIdentifier) {
                    continue;
                }

                $searchList[] = $entry;
            }


            $objectIdentifiers = $this->documentRepository->getObjectIdentifiers();

            $this->view->assign('document', $document);
            $this->view->assign('searchList', $searchList);
            $this->view->assign('alreadyImported', $objectIdentifiers);

        } catch (\Exception $exception) {
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }

            $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf');

            $this->addFlashMessage(
                $message,
                '',
                $severity,
                true
            );

            $this->redirect('list', 'Document', null);
        }

    }

    /**
     * returns the query to get latest documents
     * @return mixed
     */
    public function searchLatest()
    {
        $client = $this->clientRepository->findAll()->current();

        // get the latest documents /CREATED_DATE
        $query['body']['sort'] = array('CREATED_DATE' => array('order' => 'desc'));

        // add owner id
        $query['body']['query']['bool']['must']['term']['OWNER_ID'] = $client->getOwnerId();

        $query['body']['query']['bool']['should'][0]['query_string']['query']                       = '*';
        $query['body']['query']['bool']['should'][1]['has_child']['query']['query_string']['query'] = '*';

        $query['body']['query']['bool']['minimum_should_match'] = "1"; // 1

        // child_type is invalid in elasticsearch 7.5
        $query['body']['query']['bool']['should'][1]['has_child']['type'] = "datastream"; // 1

        return $query;
    }


    /**
     * Returns the number of items to be shown per page.
     *
     * @return int
     */
    protected function itemsPerPage()
    {
        /** @var SearchSessionData $workspaceData */
        $workspaceData = $this->session->getWorkspaceData();
        $itemsPerPage = $workspaceData->getItemsPerPage();

        $default = ($this->settings['workspaceItemsPerPage'])? $this->settings['workspaceItemsPerPage'] : 10;
        return ($itemsPerPage)? $itemsPerPage : $default;
    }


    /**
     * escapes lucene reserved characters from string
     * @param $string
     * @return mixed
     */
    private function escapeQuery($string)
    {
        $luceneReservedCharacters = preg_quote('+-&|!(){}[]^~?:\\');
        $string                   = preg_replace_callback(
            '/([' . $luceneReservedCharacters . '])/',
            function ($matches) {
                return '\\' . $matches[0];
            },
            $string
        );

        $string = str_replace("/", "\/", $string);

        return $string;
    }

}
