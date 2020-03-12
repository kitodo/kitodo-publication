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

use EWW\Dpf\Domain\Model\Bookmark;
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Security\DocumentVoter;
use EWW\Dpf\Security\Security;
use EWW\Dpf\Services\Email\Notifier;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Controller for the "workspace"/"my publications" area.
 */
class WorkspaceController  extends AbstractController
{
    const MAXIMUM_NUMBER_OF_LINKS = 5;
    const DEFAULT_SORT_FIELD = 'title';
    const DEFAULT_SORT_ORDER = 'asc';

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository = null;

    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @inject
     */
    protected $documentTypeRepository = null;

    /**
     * bookmarkRepository
     *
     * @var \EWW\Dpf\Domain\Repository\BookmarkRepository
     * @inject
     */
    protected $bookmarkRepository = null;

    /**
     * elasticSearch
     *
     * @var \EWW\Dpf\Services\ElasticSearch\ElasticSearch
     * @inject
     */
    protected $elasticSearch = null;

    /**
     * documentManager
     *
     * @var \EWW\Dpf\Services\Document\DocumentManager
     * @inject
     */
    protected $documentManager = null;

    /**
     * documentValidator
     *
     * @var \EWW\Dpf\Helper\DocumentValidator
     * @inject
     */
    protected $documentValidator;

    /**
     * editingLockService
     *
     * @var \EWW\Dpf\Services\Document\EditingLockService
     * @inject
     */
    protected $editingLockService = null;


    /**
     * list
     *
     * @param int $from
     * @return void
     */
    protected function list($from = 0)
    {
        $bookmarkIdentifiers = array();
        $bookmarks = $this->bookmarkRepository->findByFeUserUid($this->security->getUser()->getUid());
        foreach ($bookmarks as $bookmark) {
            $bookmarkIdentifiers[] = $bookmark->getDocumentIdentifier();
        }

        $filters = $this->getSessionData('workspaceFilters');
        if (!$filters) {
            $filters = [];
        }
        $excludeFilters = $this->session->getWorkspaceExcludeFilters();
        if (!$excludeFilters) {
            $excludeFilters = [];
        }

        list($sortField, $sortOrder) = $this->session->getWorkspaceSort();

        if ($this->security->getUserRole() == Security::ROLE_LIBRARIAN) {
            $query = $this->getWorkspaceQuery($from, $bookmarkIdentifiers,
                $filters, $excludeFilters, $sortField, $sortOrder);
        } elseif ($this->security->getUserRole() == Security::ROLE_RESEARCHER) {
            $query = $this->getMyPublicationsQuery($from, $bookmarkIdentifiers,
                $filters, $excludeFilters, $sortField, $sortOrder);
        }

        try {
            $results = $this->elasticSearch->search($query, 'object');
        } catch (\Exception $e) {
            $this->session->clearWorkspaceSort();
            $this->addFlashMessage(
                "Error while buildig the list!", '', AbstractMessage::ERROR
            );
        }

        if ($this->request->hasArgument('message')) {
            $this->view->assign('message', $this->request->getArgument('message'));
        }

        if ($this->request->hasArgument('errorFiles')) {
            $this->view->assign('errorFiles', $this->request->getArgument('errorFiles'));
        }


        if ($filters && $results['hits']['total']['value'] < 1) {
            $this->session->clearFilter();
            list($redirectAction, $redirectController) = $this->session->getListAction();
            $this->redirect(
                $redirectAction, $redirectController, null,
                array('message' => [], 'checkedDocumentIdentifiers' => [])
            );
        }

        $this->view->assign('documentCount', $results['hits']['total']['value']);
        $this->view->assign('documents', $results['hits']['hits']);
        $this->view->assign('pages', range(1, $results['hits']['total']['value']));
        $this->view->assign('itemsPerPage', $this->itemsPerPage());
        $this->view->assign('maximumNumberOfLinks', self::MAXIMUM_NUMBER_OF_LINKS);
        $this->view->assign('aggregations', $results['aggregations']);
        $this->view->assign('filters', $filters);
        $this->view->assign('isHideDiscarded', array_key_exists('simpleState', $excludeFilters));
        $this->view->assign('isBookmarksOnly', array_key_exists('bookmarks', $excludeFilters));
        $this->view->assign('bookmarkIdentifiers', $bookmarkIdentifiers);
    }

    /**
     * Lists documents of the workspace
     *
     * @param array $checkedDocumentIdentifiers
     *
     * @return void
     */
    protected function listWorkspaceAction($checkedDocumentIdentifiers = [])
    {
        if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {
            $this->view->assign('isWorkspace', true);
        } elseif ($this->security->getUserRole() === Security::ROLE_RESEARCHER){
            $this->view->assign('isWorkspace', false);
        } else {
            $message = LocalizationUtility::translate(
                'manager.workspace.accessDenied', 'dpf'
            );
            $this->addFlashMessage($message, '', AbstractMessage::ERROR);
        }

        $this->session->setListAction($this->getCurrentAction(), $this->getCurrentController(),
            $this->uriBuilder->getRequest()->getRequestUri()
        );

        $currentPage = null;
        $pagination = $this->getParametersSafely('@widget_0');
        if ($pagination) {
            $checkedDocumentIdentifiers = [];
            $currentPage = $pagination['currentPage'];
        } else {
            $currentPage = 1;
        }

        $this->list((empty($currentPage)? 0 : ($currentPage-1) * $this->itemsPerPage()));

        $this->view->assign('currentPage', $currentPage);
        $this->view->assign('workspaceListAction', $this->getCurrentAction());
        $this->view->assign('checkedDocumentIdentifiers', $checkedDocumentIdentifiers);
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
     * Batch operation, register documents.
     * @param array $listData
     * @throws \EWW\Dpf\Exceptions\DocumentMaxSizeErrorException
     */
    public function batchRegisterAction($listData)
    {
        $successful = [];
        $checkedDocumentIdentifiers = [];

        if (array_key_exists('documentIdentifiers', $listData) && is_array($listData['documentIdentifiers']) ) {
            $checkedDocumentIdentifiers = $listData['documentIdentifiers'];
            foreach ($listData['documentIdentifiers'] as $documentIdentifier) {

                $this->editingLockService->lock(
                    $documentIdentifier, $this->security->getUser()->getUid()
                );

                if (is_numeric($documentIdentifier)) {
                    $document = $this->documentManager->read($documentIdentifier);

                    if ($this->authorizationChecker->isGranted(DocumentVoter::REGISTER, $document)) {

                        if ($this->documentValidator->validate($document)) {

                            if (
                                $this->documentManager->update(
                                    $document,
                                    DocumentWorkflow::TRANSITION_REGISTER
                                )
                            ) {
                                $this->bookmarkRepository->addBookmark(
                                    $this->security->getUser()->getUid(), $document
                                );

                                $successful[] = $documentIdentifier;

                                $notifier = $this->objectManager->get(Notifier::class);
                                $notifier->sendRegisterNotification($document);

                                // index the document
                                $this->signalSlotDispatcher->dispatch(
                                    \EWW\Dpf\Controller\AbstractController::class,
                                    'indexDocument', [$document]
                                );
                            }
                        }
                    }
                }
            }


            $message = LocalizationUtility::translate(
                'manager.workspace.batchAction.success',
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

        list($redirectAction, $redirectController) = $this->session->getListAction();
        $this->redirect(
            $redirectAction, $redirectController, null,
            array('message' => $message, 'checkedDocumentIdentifiers' =>  $checkedDocumentIdentifiers));
    }

    /**
     * Batch operation, remove documents.
     * @param array $listData
     */
    public function batchRemoveAction($listData)
    {
        $successful = [];
        $checkedDocumentIdentifiers = [];

        if (array_key_exists('documentIdentifiers', $listData) && is_array($listData['documentIdentifiers']) ) {
            $checkedDocumentIdentifiers = $listData['documentIdentifiers'];
            foreach ($listData['documentIdentifiers'] as $documentIdentifier) {
                $feUserUid = $this->security->getUser()->getUid();
                $bookmark = $this->bookmarkRepository->findBookmark($feUserUid, $documentIdentifier);
                if ($bookmark instanceof Bookmark) {
                    $this->bookmarkRepository->remove($bookmark);
                    $successful[] = $documentIdentifier;
                }
            }

            $message = LocalizationUtility::translate(
                'manager.workspace.batchAction.success',
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

        list($redirectAction, $redirectController) = $this->session->getListAction();
        $this->redirect(
            $redirectAction, $redirectController, null,
            array('message' => $message, 'checkedDocumentIdentifiers' =>  $checkedDocumentIdentifiers));
    }


    /**
     * Batch operation, release documents.
     * @param array $listData
     */
    public function batchReleaseValidatedAction($listData)
    {
        $this->batchRelease($listData, true);
    }

    /**
     * Batch operation, release as unvalidated documents.
     * @param array $listData
     */
    public function batchReleaseUnvalidatedAction($listData)
    {
        $this->batchRelease($listData, false);
    }




    /**
     * Batch operation, release documents.
     * @param array $listData
     * @param bool $validated
     */
    protected function batchRelease($listData, $validated)
    {
        $successful = [];
        $checkedDocumentIdentifiers = [];

        if (array_key_exists('documentIdentifiers', $listData) && is_array($listData['documentIdentifiers']) ) {
            $checkedDocumentIdentifiers = $listData['documentIdentifiers'];
            foreach ($listData['documentIdentifiers'] as $documentIdentifier) {

                $this->editingLockService->lock(
                    $documentIdentifier, $this->security->getUser()->getUid()
                );

                $document = $this->documentManager->read($documentIdentifier);

                switch ($document->getState()) {
                    case DocumentWorkflow::STATE_REGISTERED_NONE:
                    case DocumentWorkflow::STATE_DISCARDED_NONE:
                    case DocumentWorkflow::STATE_POSTPONED_NONE:
                        $documentVoterAttribute = DocumentVoter::RELEASE_PUBLISH;
                        $documentWorkflowTransition = DocumentWorkflow::TRANSITION_RELEASE_PUBLISH;
                        break;

                    case DocumentWorkflow::STATE_NONE_DELETED:
                    case DocumentWorkflow::STATE_NONE_INACTIVE:
                    case DocumentWorkflow::STATE_IN_PROGRESS_DELETED:
                    case DocumentWorkflow::STATE_IN_PROGRESS_INACTIVE:
                    case DocumentWorkflow::STATE_IN_PROGRESS_ACTIVE:
                        $documentVoterAttribute = DocumentVoter::RELEASE_ACTIVATE;
                        $documentWorkflowTransition = DocumentWorkflow::TRANSITION_RELEASE_ACTIVATE;
                        break;
                    default:
                        $documentVoterAttribute = null;
                        $documentWorkflowTransition = null;
                        break;
                }

                if ($this->authorizationChecker->isGranted($documentVoterAttribute, $document)) {

                    $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData());

                    $slub->setValidation($validated);
                    $document->setSlubInfoData($slub->getSlubXml());

                    if ($this->documentManager->update($document, $documentWorkflowTransition)) {
                        $successful[] = $documentIdentifier;

                        $this->bookmarkRepository->removeBookmark(
                            $document, $this->security->getUser()->getUid()
                        );

                        //$notifier = $this->objectManager->get(Notifier::class);
                        //$notifier->sendRegisterNotification($document);
                    }
                }
            }

            $message = LocalizationUtility::translate(
                'manager.workspace.batchAction.success',
                'dpf',
                [sizeof($successful), sizeof($listData['documentIdentifiers'])]
            );
            $this->addFlashMessage(
                $message, '',
                (sizeof($successful) > 0 ? AbstractMessage::OK : AbstractMessage::WARNING)
            );

            if (sizeof($successful) === 1 ) {
                $this->addFlashMessage(
                    "1 ".LocalizationUtility::translate("manager.workspace.bookmarkRemoved", "dpf"),
                    '',
                    AbstractMessage::INFO
                );
            }

            if (sizeof($successful) > 1 ) {
                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        "manager.workspace.bookmarksRemoved", "dpf", [sizeof($successful)]
                    ),
                    '',
                    AbstractMessage::INFO
                );
            }

        } else {
            $message = LocalizationUtility::translate(
                'manager.workspace.batchAction.failure',
                'dpf');
            $this->addFlashMessage($message, '', AbstractMessage::ERROR);
        }

        list($redirectAction, $redirectController) = $this->session->getListAction();
        $this->redirect(
            $redirectAction, $redirectController, null,
            array('message' => $message, 'checkedDocumentIdentifiers' =>  $checkedDocumentIdentifiers));

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
     *
     * @return array
     */
    protected function getWorkspaceQuery(
        $from = 0, $bookmarkIdentifiers = [], $filters= [], $excludeFilters = [], $sortField = null, $sortOrder = null
    )
    {
        $workspaceFilter = [
            'bool' => [
                'must' => [
                    [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'creator' => $this->security->getUser()->getUid()
                                    ]
                                ],
                                [
                                    'bool' => [
                                        'should' => [
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

        return $this->buildQuery(
            $workspaceFilter, $from, $bookmarkIdentifiers, $filters,
            $excludeFilters, $sortField, $sortOrder
        );
    }


    /**
     * get list view data for the my publications list.
     *
     * @param int $from
     * @param array $bookmarkIdentifiers
     * @param array $filters
     * @param array $excludeFilters
     * @param string $sortField
     * @param string $sortOrder
     *
     * @return array
     */
    protected function getMyPublicationsQuery(
        $from = 0, $bookmarkIdentifiers = [], $filters= [], $excludeFilters = [], $sortField = null, $sortOrder = null
    )
    {
        $workspaceFilter = [
            'bool' => [
                'must' => [
                    [
                        'term' => [
                            'creator' => $this->security->getUser()->getUid()
                        ]
                    ]
                ]
            ]
        ];

        return $this->buildQuery(
            $workspaceFilter, $from, $bookmarkIdentifiers, $filters,
            $excludeFilters, $sortField, $sortOrder
        );
    }

    /**
     * Builds the document list query.
     *
     * @param array $workspaceFilter
     * @param int $from
     * @param array $bookmarkIdentifiers
     * @param array $filters
     * @param array $excludeFilters
     * @param string $sortField
     * @param string $sortOrder
     *
     * @return array
     */
    protected function buildQuery(
        $workspaceFilter, $from = 0, $bookmarkIdentifiers = [], $filters = [], $excludeFilters = [], $sortField = null, $sortOrder = null
    )
    {
        // The base filter.
        $queryFilter = [
            'bool' => [
                'must' => [
                    [
                        'bool' => [
                            'should' => [
                                0 => $workspaceFilter
                            ]
                        ]
                    ]
                ]
            ]
        ];

        if (!($excludeFilters && array_key_exists('bookmarks', $excludeFilters))) {
            // Add user document bookmarks.
            if ($bookmarkIdentifiers) {
                $queryFilter['bool']['must'][0]['bool']['should'][] = [
                    'terms' => [
                        '_id' => $bookmarkIdentifiers
                    ]
                ];
            }
        } else {
            // Show only user document bookmarks.
            $queryFilter['bool']['must'][0] = [
                'terms' => [
                    '_id' => $bookmarkIdentifiers
                ]
            ];
        }

        $filterPart = $this->buildFilterQueryPart($filters, $excludeFilters);

        if ($filterPart) {
            $queryFilter['bool']['must'][] = $filterPart;
        }

        // Put together the complete query.
        $query = [
            'body' => [
                'size' => $this->itemsPerPage(),
                'from' => $from,
                'query' => [
                    'bool' => [
                        'must' => [
                            'match_all' => (object)[]
                        ],
                        'filter' => $queryFilter
                    ]
                ],
                'sort' => $this->buildSortQueryPart($sortField, $sortOrder),
                'aggs' => [
                    'simpleState' => [
                        'terms' => [
                            'field' => 'simpleState'
                        ]
                    ],
                    'year' => [
                        'terms' => [
                            'field' => 'year'
                        ]
                    ],
                    'doctype' => [
                        'terms' => [
                            'field' => 'doctype'
                        ]
                    ],
                    'hasFiles' => [
                        'terms' => [
                            'field' => 'hasFiles'
                        ]
                    ],
                    'universityCollection' => [
                        'terms' => [
                            'script' => [
                                'lang' => 'painless',
                                'source' =>
                                    "for (int i = 0; i < doc['collections'].length; ++i) {".
                                    "    if(doc['collections'][i] =='".$this->settings['universityCollection']."') {".
                                    "        return 'true';".
                                    "    }".
                                    "}".
                                    "return 'false';"
                                ]
                        ]
                    ],
                    'author' => [
                        'terms' => [
                            'field' => 'author'
                        ]
                    ],
                    'creatorRole' => [
                        'terms' => [
                            'script' => [
                                'lang' => 'painless',
                                'source' =>
                                    //"if (doc['creator'].size() == 0) { return 'unknown'; }".
                                    "if (".
                                    "    doc['creatorRole'].value == '".Security::ROLE_LIBRARIAN."' &&".
                                    "    doc['creator'].value != '".$this->security->getUser()->getUid()."'".
                                    ") {".
                                    "    return 'librarian';".
                                    "}".
                                    "if (doc['creator'].value == '".$this->security->getUser()->getUid()."') {".
                                    "    return 'self';".
                                    "}".
                                    "if (doc['creatorRole'].value == '".Security::ROLE_RESEARCHER."') {".
                                    "    return 'user';".
                                    "}".
                                    "return 'unknown';"
                            ]
                        ]
                    ]

                ]
            ]
        ];

        return $query;
    }

    /**
     * Composes the filter part based on the given filters.
     *
     * @param array $filters
     * @param array $excludeFilters
     * @return array
     */
    protected function buildFilterQueryPart($filters, $excludeFilters = []) {

        $queryFilter = [];

        // Build the column filter part.
        if ($filters && is_array($filters)) {

            $validKeys = [
                'simpleState', 'author', 'doctype', 'hasFiles', 'year', 'universityCollection', 'creatorRole'
            ];

            foreach ($filters as $key => $filterValues) {
                $queryFilterPart = [];
                if (in_array($key, $validKeys, true)) {
                    if ($key == 'universityCollection') {
                        if ($filterValues && is_array($filterValues)) {
                            if (in_array("true", $filterValues)) {
                                $filterValue = $this->settings['universityCollection'];
                                $queryFilterPart['bool']['should'][] = [
                                    'term' => [
                                        'collections' => $filterValue
                                    ]
                                ];
                            } else {
                                $filterValue = $this->settings['universityCollection'];
                                $queryFilterPart['bool']['should'][] = [
                                    'bool' => [
                                        'must_not' => [
                                            'term' => [
                                                'collections' => $filterValue
                                            ]
                                        ]
                                    ]
                                ];
                            }
                            $queryFilter['bool']['must'][] = $queryFilterPart;
                        }
                    } elseif ($key == 'creatorRole') {
                        $queryFilterPart = [];
                        if ($filterValues && is_array($filterValues)) {
                            if (in_array("librarian", $filterValues)) {
                                $creatorRolePart['bool']['must'] = [
                                    [
                                        'term' => [
                                            'creatorRole' => Security::ROLE_LIBRARIAN
                                        ]
                                    ],
                                    [
                                        'bool' => [
                                            'must_not' => [
                                                'term' => [
                                                    'creator' => $this->security->getUser()->getUid()
                                                ]
                                            ]
                                        ]
                                    ]
                                ];
                                $queryFilterPart['bool']['should'][] = $creatorRolePart;
                            } elseif (in_array("user", $filterValues)) {
                                $creatorRolePart['bool']['must'] = [
                                    [
                                        'term' => [
                                            'creatorRole' => Security::ROLE_RESEARCHER
                                        ]
                                    ],
                                    [
                                        'bool' => [
                                            'must_not' => [
                                                'term' => [
                                                    'creator' => $this->security->getUser()->getUid()
                                                ]
                                            ]
                                        ]
                                    ]
                                ];
                                $queryFilterPart['bool']['should'][] = $creatorRolePart;
                            } elseif (in_array("self", $filterValues)) {
                                $creatorRolePart['bool']['must'] = [
                                    [
                                        'term' => [
                                            'creator' =>  $this->security->getUser()->getUid()
                                        ]
                                    ]
                                ];
                                $queryFilterPart['bool']['should'][] = $creatorRolePart;
                            } else {
                                $creatorRolePart['bool']['must'] = [
                                    [
                                        'bool' => [
                                            'must_not' => [
                                                'term' => [
                                                    'creator' => $this->security->getUser()->getUid()
                                                ]
                                            ]
                                        ]
                                    ],
                                    [
                                        'bool' => [
                                            'must_not' => [
                                                'term' => [
                                                    'creatorRole' => Security::ROLE_LIBRARIAN
                                                ]
                                            ]
                                        ]
                                    ],
                                    [
                                        'bool' => [
                                            'must_not' => [
                                                'term' => [
                                                    'creatorRole' => Security::ROLE_RESEARCHER
                                                ]
                                            ]
                                        ]
                                    ]
                                ];
                                $queryFilterPart['bool']['should'][] = $creatorRolePart;
                            }

                            if ($queryFilterPart) {
                                $queryFilter['bool']['must'][] = $queryFilterPart;
                            }
                        }
                    } else {
                        if ($filterValues && is_array($filterValues)) {
                            foreach ($filterValues as $filterValue) {
                                $queryFilterPart['bool']['should'][] = [
                                    'term' => [
                                        $key => $filterValue
                                    ]
                                ];
                            }
                            $queryFilter['bool']['must'][] = $queryFilterPart;
                        }
                    }
                }
            }
        }

        if ($excludeFilters && array_key_exists('simpleState', $excludeFilters)) {
            if ($excludeFilters['simpleState']) {
                foreach ($excludeFilters['simpleState'] as $simpleStateExclude) {
                    $queryFilter['bool']['must'][] = [
                        'bool' => [
                            'must_not' => [
                                'bool' => [
                                    'must' => [
                                        [
                                            'term' => [
                                                'simpleState' => $simpleStateExclude
                                            ]
                                        ],
                                        [
                                            'term' => [
                                                'creator' => $this->security->getUser()->getUid()
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ];
                }
            }
        }

        return $queryFilter;
    }


    /**
     * Composes the sort query part based on the given sort field and order.
     *
     * @param string $sortField
     * @param string $sortOrder
     * @return array
     */
    protected function buildSortQueryPart($sortField, $sortOrder) {
        // Build the sorting part.
        $script = "";
        if ($sortField == "simpleState") {
            $script = $this->getSortScriptState();
        } elseif ($sortField == "universityCollection") {
            $script = $this->getSortScriptUniversityCollection($this->settings['universityCollection']);
        } elseif ($sortField == "hasFiles") {
            $script = $this->getSortScriptHasFiles();
        } elseif ($sortField == "creatorRole") {
            $script = $this->getSortScriptCreatorRole($this->security->getUser()->getUid());
        }

        if ($script) {
            $sort = [
                "_script" => [
                    "type" => "string",
                    "order" => $sortOrder,
                    "script" => [
                        "lang" => "painless",
                        "source" => $script
                    ]
                ],
                "title.keyword" => [
                    "order" => "asc"
                ]
            ];
        } else {
            if ($sortField == 'title') {
                $sortField.= ".keyword";
            }

            $sort = [
                (($sortField)? $sortField : self::DEFAULT_SORT_FIELD.".keyword") => [
                    'order' => (($sortOrder)? $sortOrder : self::DEFAULT_SORT_ORDER)
                ]
            ];
        }

        return $sort;
    }


    protected function getSortScriptUniversityCollection($collection)
    {
        $script  = "for (int i = 0; i < doc['collections'].length; ++i) {";
        $script .= "    if (doc['collections'][i] == '".$collection."') {";
        $script .= "        return '1';";
        $script .= "    }";
        $script .= "}";
        $script .= "return '2'";

        return $script;
    }

    protected function getSortScriptHasFiles()
    {
        $script = "if (doc['hasFiles'].value == 'true') {";
        $script .= "    return '1';";
        $script .= "}";
        $script .= "return '2'";

        return $script;
    }

    protected function getSortScriptCreatorRole($feUserUid)
    {
        $script = "if (doc['creator'].value == '".$feUserUid."') {";
        $script .= "    return '1';";
        $script .= "}";
        $script .= "if (doc['creatorRole'].value == '".Security::ROLE_LIBRARIAN."') {";
        $script .= "return '2';";
        $script .= "}";
        $script .= "if (doc['creatorRole'].value == '".Security::ROLE_RESEARCHER."') {";
        $script .= "    return '3';";
        $script .= "}";
        $script .= "return '4';";

        return $script;
    }


    protected function getSortScriptState()
    {
        $sortStates = [];
        foreach (DocumentWorkflow::PLACES as $state) {
            if (array_key_exists($state, DocumentWorkflow::STATE_TO_SIMPLESTATE_MAPPING)) {
                $simpleState = DocumentWorkflow::STATE_TO_SIMPLESTATE_MAPPING[$state];
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:manager.documentList.state.'.$simpleState;
                $stateName = LocalizationUtility::translate($key, 'dpf');
                $sortStates[] = "if (doc['state'].value == '".$state."') return '".$stateName."';";
            }
        }

        $sortStates = implode(" ", $sortStates);

        return $sortStates." return '';";
    }


    protected function getSortScriptDoctype()
    {
        $sortDoctypes = [];
        foreach ($this->documentTypeRepository->findAll() as $documentType) {
            if ($documentType->getName() && $documentType->getDisplayname()) {
                $sortDoctypes[] = "if (doc['doctype'].value == '".$documentType->getName()."')"
                    ." return '".$documentType->getDisplayname()."';";
            }
        }

        $sortDoctypes = implode(" ", $sortDoctypes);

        return $sortDoctypes." return '';";
    }


    /**
     * A temporary solution to initialize the index.
     *
     * @param int $start
     * @param int $stop
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function initIndexAction($start=1, $stop=100)
    {
        /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
        $signalSlotDispatcher = $this->objectManager->get(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);

        /** @var \EWW\Dpf\Services\Transfer\DocumentTransferManager $documentTransferManager */
        $documentTransferManager = $this->objectManager->get(\EWW\Dpf\Services\Transfer\DocumentTransferManager::class);

        $fedoraRepository = $this->objectManager->get(\EWW\Dpf\Services\Transfer\FedoraRepository::class);
        $documentTransferManager->setRemoteRepository($fedoraRepository);

        for($i=$start; $i<$stop; $i++) {
            try {
                $document = $documentTransferManager->retrieve('qucosa:' . $i);

                if ($document instanceof Document) {
                    $state = $document->getState();
                    $document->setState(
                        str_replace(
                            DocumentWorkflow::LOCAL_STATE_IN_PROGRESS,
                            DocumentWorkflow::LOCAL_STATE_NONE,
                            $state
                        )
                    );

                    // index the document
                    $signalSlotDispatcher->dispatch(
                        \EWW\Dpf\Controller\AbstractController::class,
                        'indexDocument', [$document]
                    );

                    $this->documentRepository->remove($document);
                }
            } catch (\EWW\Dpf\Exceptions\RetrieveDocumentErrorException $e) {
                // Nothing to be done.
            }
        }

        foreach ($this->documentRepository->findAll() as $document) {
            if (!$document->isTemporary() && !$document->isSuggestion()) {
                // index the document
                $signalSlotDispatcher->dispatch(
                    \EWW\Dpf\Controller\AbstractController::class,
                    'indexDocument', [$document]
                );
            }
        }
    }


    /**
     * action uploadFiles
     *
     * @param string $documentIdentifier
     * @return void
     */
    public function uploadFilesAction($documentIdentifier)
    {
        $document = $this->documentManager->read(
            $documentIdentifier,
            $this->security->getUser()->getUID()
        );

        if ($document instanceof Document) {
            if ($this->authorizationChecker->isGranted(DocumentVoter::EDIT, $document)) {
                $this->redirect(
                    'edit',
                    'DocumentFormBackoffice',
                    null,
                    ['document' => $document, 'activeFileTab' => true]);
            } elseif ($this->authorizationChecker->isGranted(DocumentVoter::SUGGEST_MODIFICATION, $document)) {
                $this->redirect(
                    'edit',
                    'DocumentFormBackoffice',
                    null,
                    ['document' => $document, 'suggestMod' => true, 'activeFileTab' => true]);
            } else {
                if ($document->getOwner() !== $this->security->getUser()->getUid()) {
                    $message = LocalizationUtility::translate(
                        'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_edit.accessDenied',
                        'dpf',
                        array($document->getTitle())
                    );
                } else {
                    $message = LocalizationUtility::translate(
                        'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_edit.failureBlocked',
                        'dpf',
                        array($document->getTitle())
                    );
                }
            }
        } else {
            $message = LocalizationUtility::translate(
                'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected',
                'dpf'
            );
        }

        $this->addFlashMessage($message, '', AbstractMessage::ERROR);

        list($action, $controller, $redirectUri) = $this->session->getListAction();

        if ($redirectUri) {
            $this->redirectToUri($redirectUri);
        } else {
            $this->redirect($action, $controller, null, array('message' => $message));;
        }

    }


    /**
     * Returns the number of items to be shown per page.
     *
     * @return int
     */
    protected function itemsPerPage()
    {
        $itemsPerPage = $this->session->getWorkspaceItemsPerPage();
        $default = ($this->settings['workspaceItemsPerPage'])? $this->settings['workspaceItemsPerPage'] : 10;
        return ($itemsPerPage)? $itemsPerPage : $default;
    }

}
