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
use EWW\Dpf\Session\SearchSessionData;

/**
 * Controller for the "workspace"/"my publications" area.
 */
class WorkspaceController extends AbstractController
{
    /**
     * FrontendUserRepository
     *
     * @var  TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $frontendUserRepository;

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentRepository = null;

    /**
     * documentTypeRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentTypeRepository = null;

    /**
     * bookmarkRepository
     *
     * @var \EWW\Dpf\Domain\Repository\BookmarkRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $bookmarkRepository = null;

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
     * documentManager
     *
     * @var \EWW\Dpf\Services\Document\DocumentManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentManager = null;

    /**
     * documentValidator
     *
     * @var \EWW\Dpf\Helper\DocumentValidator
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentValidator;

    /**
     * editingLockService
     *
     * @var \EWW\Dpf\Services\Document\EditingLockService
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $editingLockService = null;


    /**
     * metadataGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $metadataGroupRepository;

    /**
     * fisDataService
     *
     * @var \EWW\Dpf\Services\FeUser\FisDataService
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $fisDataService = null;

    /**
     * documentStorage
     *
     * @var \EWW\Dpf\Services\Storage\DocumentStorage
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentStorage = null;

    public function initializeAction()
    {
        $this->authorizationChecker->denyAccessUnlessLoggedIn();

        parent::initializeAction();
    }

    /**
     * list
     *
     * @param int $from
     * @return void
     */
    protected function list($from = 0)
    {
        $bookmarkIdentifiers = [];
        foreach ($this->bookmarkRepository->findByFeUserUid($this->security->getUser()->getUid()) as $bookmark) {
            $bookmarkIdentifiers[] = $bookmark->getDocumentIdentifier();
        }

        /** @var SearchSessionData $workspaceSessionData */
        $workspaceSessionData = $this->session->getWorkspaceData();
        $filters = $workspaceSessionData->getFilters();
        $excludeFilters = $workspaceSessionData->getExcludeFilters();
        $sortField = $workspaceSessionData->getSortField();
        $sortOrder = $workspaceSessionData->getSortOrder();

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
            $workspaceSessionData->clearSort();
            $workspaceSessionData->clearFilters();
            $this->session->setWorkspaceData($workspaceSessionData);
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
        $this->view->assign('bookmarkIdentifiers', $bookmarkIdentifiers);

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
     * Lists documents of the workspace
     *
     * @param array $checkedDocumentIdentifiers
     *
     * @return void
     */
    protected function listWorkspaceAction($checkedDocumentIdentifiers = [])
    {
        $args = $this->request->getArguments();
        if ($args['refresh']) {
            $workspaceSessionData = $this->session->getWorkspaceData();
            $workspaceSessionData->clearSort();
            $workspaceSessionData->clearFilters();

            $this->session->setWorkspaceData($workspaceSessionData);
        }

        if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {
            $this->view->assign('isWorkspace', true);
        } elseif ($this->security->getUserRole() === Security::ROLE_RESEARCHER) {
            $this->view->assign('isWorkspace', false);
        } else {
            $message = LocalizationUtility::translate(
                'manager.workspace.accessDenied', 'dpf'
            );
            $this->addFlashMessage($message, '', AbstractMessage::ERROR);
        }

        $this->session->setStoredAction($this->getCurrentAction(), $this->getCurrentController(),
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
                                    $document,
                                    $this->security->getUser()->getUid()
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


            if (sizeof($successful) == 1) {
                $locallangKey = 'manager.workspace.batchAction.register.success.singular';
            } else {
                $locallangKey = 'manager.workspace.batchAction.register.success.plural';
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
     * Batch operation, set documents to "In progress".
     * @param array $listData
     */
    public function batchSetInProgressAction($listData)
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

                    if ($this->authorizationChecker->isGranted(DocumentVoter::UPDATE, $document)) {

                        $document->setTemporary(false);

                            if (
                                $this->documentManager->update(
                                    $document,
                                    DocumentWorkflow::TRANSITION_IN_PROGRESS
                                )
                            ) {
                                $successful[] = $documentIdentifier;

                                // index the document
                                $this->signalSlotDispatcher->dispatch(
                                    \EWW\Dpf\Controller\AbstractController::class,
                                    'indexDocument', [$document]
                                );
                            }
                    }
            }

            if (sizeof($successful) == 1) {
                $locallangKey = 'manager.workspace.batchAction.setInProgress.success.singular';
            } else {
                $locallangKey = 'manager.workspace.batchAction.setInProgress.success.plural';
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


            if (sizeof($successful) == 1) {
                $locallangKey = 'manager.workspace.batchAction.remove.success.singular';
            } else {
                $locallangKey = 'manager.workspace.batchAction.remove.success.plural';
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

                    $internalFormat = new \EWW\Dpf\Helper\InternalFormat($document->getXmlData());
                    $internalFormat->setValidation($validated);
                    $document->setXmlData($internalFormat->getXml());

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

            if (sizeof($successful) == 1) {
                $locallangKey = 'manager.workspace.batchAction.release.success.singular';
            } else {
                $locallangKey = 'manager.workspace.batchAction.release.success.plural';
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

            if (sizeof($successful) === 1 ) {
                $this->addFlashMessage(
                    "1 ".LocalizationUtility::translate("manager.workspace.bookmarkRemoved.singular", "dpf"),
                    '',
                    AbstractMessage::INFO
                );
            }

            if (sizeof($successful) > 1 ) {
                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        "manager.workspace.bookmarkRemoved.plural", "dpf", [sizeof($successful)]
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

        list($redirectAction, $redirectController) = $this->session->getStoredAction();
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
        $from = 0, $bookmarkIdentifiers = [], $filters = [], $excludeFilters = [], $sortField = null, $sortOrder = null
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

        return $this->queryBuilder->buildQuery(
            $this->itemsPerPage(), $workspaceFilter, $from, $bookmarkIdentifiers, $filters,
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
        $from = 0, $bookmarkIdentifiers = [], $filters = [], $excludeFilters = [], $sortField = null, $sortOrder = null
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
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $fisPersIdFilter =  [
            'bool' => [
                'must' => [
                    [
                        'term' => [
                            'fobIdentifiers' => $this->security->getFisPersId()
                        ]
                    ],
                    [
                        'bool' => [
                            'must_not' => [
                                'term' => [
                                    'state' => DocumentWorkflow::STATE_NEW_NONE
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ];

        if ($this->security->getFisPersId()) {
            $workspaceFilter['bool']['must'][0]['bool']['should'][1] = $fisPersIdFilter;
        }

        return $this->queryBuilder->buildQuery(
            $this->itemsPerPage(), $workspaceFilter, $from, $bookmarkIdentifiers, $filters,
            $excludeFilters, $sortField, $sortOrder
        );
    }

    /**
     * Action editDocument
     *
     * @param string $documentIdentifier
     * @param string $activeGroup
     * @param int $activeGroupIndex
     * @return void
     */
    public function editDocumentAction($documentIdentifier, $activeGroup = '', $activeGroupIndex = 0)
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
                    [
                        'document' => $document,
                        'activeGroup' => $activeGroup,
                        'activeGroupIndex' => $activeGroupIndex
                    ]
                );
            } elseif ($this->authorizationChecker->isGranted(DocumentVoter::SUGGEST_MODIFICATION, $document)) {
                $this->redirect(
                    'edit',
                    'DocumentFormBackoffice',
                    null,
                    [
                        'document' => $document,
                        'suggestMod' => true,
                        'activeGroup' => $activeGroup,
                        'activeGroupIndex' => $activeGroupIndex
                    ]
                );
            } else {
                if ($document->getCreator() !== $this->security->getUser()->getUid()) {
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

        list($action, $controller, $redirectUri) = $this->session->getStoredAction();

        if ($redirectUri) {
            $this->redirectToUri($redirectUri);
        } else {
            $this->redirect($action, $controller, null, array('message' => $message));
        }

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

}
