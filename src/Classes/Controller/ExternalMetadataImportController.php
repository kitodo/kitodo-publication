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
use EWW\Dpf\Security\Security;
use EWW\Dpf\Services\ImportExternalMetadata\Importer;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use EWW\Dpf\Domain\Model\ExternalMetadata;
use EWW\Dpf\Services\ImportExternalMetadata\CrossRefImporter;
use EWW\Dpf\Services\ImportExternalMetadata\DataCiteImporter;
use EWW\Dpf\Services\ImportExternalMetadata\PubMedImporter;
use EWW\Dpf\Services\ImportExternalMetadata\K10plusImporter;
use EWW\Dpf\Session\BulkImportSessionData;


/**
 * ExternalDataImportController
 */
class ExternalMetadataImportController extends AbstractController
{
    /**
     * ExternalMetadataRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ExternalMetadataRepository
     * @inject
     */
    protected $externalMetadataRepository = null;

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
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @inject
     */
    protected $persistenceManager;

    /**
     * elasticSearch
     *
     * @var \EWW\Dpf\Services\ElasticSearch\ElasticSearch
     * @inject
     */
    protected $elasticSearch = null;

    /**
     * queryBuilder
     *
     * @var \EWW\Dpf\Services\ElasticSearch\QueryBuilder
     * @inject
     */
    protected $queryBuilder = null;

    /**
     * bookmarkRepository
     *
     * @var \EWW\Dpf\Domain\Repository\BookmarkRepository
     * @inject
     */
    protected $bookmarkRepository = null;

    /**
     * workflow
     *
     * @var \EWW\Dpf\Domain\Workflow\DocumentWorkflow
     */
    protected $workflow;

    /**
     * DocumentController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param string $query
     */
    public function bulkStartAction($query = '')
    {
        /** @var BulkImportSessionData $bulkImportSessionData */
        $bulkImportSessionData = $this->session->getBulkImportData();

        $crossRefAuthorSearch = $bulkImportSessionData->getCrossRefSearchField() === 'author';
        $pubMedAuthorSearch = true;

        $this->externalMetadataRepository->clearExternalMetadataByFeUserUid($this->security->getUser()->getUid());
        $this->view->assign('crossRefAuthorSearch', $crossRefAuthorSearch);
        $this->view->assign('pubMedAuthorSearch', $pubMedAuthorSearch);
        $this->view->assign('query', $query);
    }

    /**
     * @param string $query
     */
    public function bulkSearchCrossRefAction($query = '')
    {
        /** @var BulkImportSessionData $bulkImportSessionData */
        $bulkImportSessionData = $this->session->getBulkImportData();

        $currentPage = null;
        $pagination = $this->getParametersSafely('@widget_0');
        if ($pagination) {
            $currentPage = $pagination['currentPage'];
            $query = $bulkImportSessionData->getCrossRefQuery();
        } else {
            if (empty($query)) {
                $this->redirect('bulkStart');
            }

            $bulkImportSessionData->setCrossRefQuery($query);
            $currentPage = 1;
        }

        $offset = empty($currentPage)? 0 : ($currentPage-1) * $this->itemsPerPage();

        /** @var Importer $importer */
        $importer = $this->objectManager->get(CrossRefImporter::class);
        $results = $importer->search(
            $query,
            $this->itemsPerPage(),
            $offset,
            $bulkImportSessionData->getCrossRefSearchField()
        );

        $bulkImportSessionData->setCurrentMetadataItems(($results? $results['items'] : []));
        $this->session->setBulkImportData($bulkImportSessionData);

        $this->forward(
            'bulkResults',
            null,
            null,
            [
                'results' => $results,
                'query' => $query,
                'currentPage' => $currentPage
            ]
        );
    }

    /**
     * @param string $query
     */
    public function bulkSearchPubMedAction($query = '')
    {
        /** @var BulkImportSessionData $bulkImportSessionData */
        $bulkImportSessionData = $this->session->getBulkImportData();

        $currentPage = null;
        $pagination = $this->getParametersSafely('@widget_0');
        if ($pagination) {
            $currentPage = $pagination['currentPage'];
            $query = $bulkImportSessionData->getPubMedQuery();
        } else {
            if (empty($query)) {
                $this->redirect('bulkStart');
            }

            $bulkImportSessionData->setPubMedQuery($query);
            $currentPage = 1;
        }

        $offset = empty($currentPage)? 0 : ($currentPage-1) * $this->itemsPerPage();

        /** @var Importer $importer */
        $importer = $this->objectManager->get(PubMedImporter::class);
        $results = $importer->search(
            $query,
            $this->itemsPerPage(),
            $offset,
            ''
        );

        $bulkImportSessionData->setCurrentMetadataItems(($results? $results['items'] : []));
        $this->session->setBulkImportData($bulkImportSessionData);

        $this->forward(
            'bulkResults',
            null,
            null,
            [
                'results' => $results,
                'query' => $query,
                'currentPage' => $currentPage
            ]
        );
    }

    /**
     * @param string $query
     * @param array $results
     * @param int $currentPage
     */
    public function bulkResultsAction($query, $results = null, $currentPage = 1)
    {
        $externalMetadata = $this->externalMetadataRepository->findByFeUser($this->security->getUser()->getUid());
        $checkedPublicationIdentifiers = [];
        /** @var ExternalMetadata $data */
        foreach ($externalMetadata as $data) {
            $checkedPublicationIdentifiers[] = $data->getPublicationIdentifier();
        }

        $this->view->assign('totalResults', $results['total-results']);
        $this->view->assign('itemsPerPage', $this->itemsPerPage());
        $this->view->assign('currentPage', $currentPage);
        $this->view->assign('query', $query);
        $this->view->assign('checkedPublicationIdentifiers', $checkedPublicationIdentifiers);
        $this->view->assign('results', $results);
    }

    /**
     *
     */
    function bulkImportAction()
    {
        $importCounter = ['imported' => 0, 'bookmarked' => 0, 'total' => 0];

        try {
            $externalMetadata = $this->externalMetadataRepository->findByFeUser($this->security->getUser()->getUid());

            $importedDocuments = [];
            $importedDocumentIdentifiers = [];

            /** @var ExternalMetadata $externalMetadataItem */
            foreach ($externalMetadata as $externalMetadataItem) {

                /** @var  Importer $importer */
                $importer = $this->objectManager->get($externalMetadataItem->getSource());

                /*
                if (!$document_in_kitodo) {
                    if ($document_in_workspace) {
                        // bookmark
                        $importCounter['bookmarked'] += 1;
                    } else {
                        // create
                        $importCounter['imported'] += 1;
                    }
                } else {

                }
                */
                /** @var Document $newDocument */
                $newDocument = $importer->import($externalMetadataItem);

                if ($newDocument instanceof Document) {
                    $this->documentRepository->add($newDocument);
                    $this->externalMetadataRepository->remove($externalMetadataItem);
                    $importedDocuments[] = $newDocument;
                    $importCounter['imported'] += 1;
                }

            }

            $this->persistenceManager->persistAll();

            // Documents can only be indexed after they have been persisted as we need a valid UID.
            /** @var Document $importedDocument */
            foreach ($importedDocuments as $importedDocument) {
                // index the document
                $this->signalSlotDispatcher->dispatch(
                    AbstractController::class, 'indexDocument', [$importedDocument]
                );
                $importedDocumentIdentifiers[] = $importedDocument->getDocumentIdentifier();
            }

            /** @var BulkImportSessionData $bulkImportSessionData */
            $bulkImportSessionData = $this->session->getBulkImportData();
            $bulkImportSessionData->setLatestImportIdentifiers($importedDocumentIdentifiers);
            $this->session->setBulkImportData($bulkImportSessionData);

        } catch(\Throwable $throwable) {
            $this->logger->error($throwable->getMessage());

            $message = LocalizationUtility::translate(
                'manager.importMetadata.publicationNotImported', 'dpf'
            );

            $this->addFlashMessage($message, '', AbstractMessage::ERROR);
        }

        $this->redirect(
            'bulkImportedDocuments',
            null,
            null,
            ['from' => 0]);
    }

    /**
     * Cancels the bulk import result list view.
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    function cancelBulkImportAction()
    {
        $this->redirect('bulkStart');
    }


    /**
     * Shows the form to find a publication by an identifier
     * @param string $identifier
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function findAction($identifier = '')
    {
        $this->externalMetadataRepository->clearExternalMetadataByFeUserUid($this->security->getUser()->getUid());
        $this->view->assign('identifier', $identifier);
    }

    /**
     * Retrieves and caches the the metadata related to the given identifier.
     *
     * @param string $identifier
     */
    public function retrieveAction($identifier)
    {
        $identifier = trim($identifier);

        if (empty($identifier)) {
            $this->redirect('find');
        }

        // Search if the document alreday exists in the workspace or my publications
        $results = $this->findDocumentInWorkspace($identifier);
        if (is_array($results) && $results['hits']['total']['value'] > 0) {

            if ($this->security->getUser()->getUserRole() == Security::ROLE_LIBRARIAN) {
                $message = LocalizationUtility::translate(
                    'manager.importMetadata.alreadyInWorkspace', 'dpf'
                );
            } else {
                $message = LocalizationUtility::translate(
                    'manager.importMetadata.alreadyInMyPublications', 'dpf'
                );
            }
            $this->addFlashMessage($message, '', AbstractMessage::ERROR);

            $this->redirect('find', null, null, ['identifier' => $identifier]);
        }

        // Search if the document already exists in kitodo.
        $query = $this->queryBuilder->buildQuery(
            1, [] , 0, [], [], [], null, null, 'identifier:"'.$identifier.'"'
        );
        $results = $this->elasticSearch->search($query, 'object');

        if (is_array($results) && $results['hits']['total']['value'] > 0) {

            $this->bookmarkRepository->addBookmark(
                $results['hits']['hits'][0]['_id'],
                $this->security->getUser()->getUid()
            );

            if ($this->security->getUser()->getUserRole() == Security::ROLE_LIBRARIAN) {
                $message = LocalizationUtility::translate(
                    'manager.importMetadata.alreadyInSystemWorkspace', 'dpf'
                );
            } else {
                $message = LocalizationUtility::translate(
                    'manager.importMetadata.alreadyInSystemMyPublications', 'dpf'
                );
            }
            $this->addFlashMessage($message, '', AbstractMessage::ERROR);

            $this->redirect('find', null, null, ['identifier' => $identifier]);
        }

        /** @var \EWW\Dpf\Services\ImportExternalMetadata\Importer $importer */
        $importer = null;

        // Choose the right data provider depending on the identifier type and retrieve the metadata.
        $identifierType = $this->determineIdentifierType($identifier);

        if ($identifierType === 'DOI') {
            $importer = $this->objectManager->get(CrossRefImporter::class);
            $externalMetadata = $importer->findByIdentifier($identifier);
            if (!$externalMetadata) {
                $importer = $this->objectManager->get(DataCiteImporter::class);
                $externalMetadata = $importer->findByIdentifier($identifier);
            }
        } elseif ($identifierType === 'PMID') {
            $importer = $this->objectManager->get(PubMedImporter::class);
            $externalMetadata = $importer->findByIdentifier($identifier);
        } elseif ($identifierType === 'ISBN') {
            $importer = $this->objectManager->get(K10plusImporter::class);
            $externalMetadata = $importer->findByIdentifier(str_replace('- ', '', $identifier));
        } else {
            $externalMetadata = null;
        }

        if ($externalMetadata) {
            // Save the metadata for further processing
            $this->externalMetadataRepository->add($externalMetadata);
            $this->persistenceManager->persistAll();
        }

        if ($externalMetadata) {
            $this->redirect(
                'import',
                null,
                null,
                ['externalMetadata'=>$externalMetadata]
            );
        } else {
            $message = LocalizationUtility::translate(
                'manager.importMetadata.nothingFound', 'dpf'
            );

            $this->addFlashMessage(
                $message,
                '',
                AbstractMessage::ERROR
            );
            $this->redirect('find', null, null, ['identifier' => $identifier]);
        }

    }

    /**
     * The import dialog
     *
     * @param ExternalMetadata $externalMetadata
     */
    public function importAction(ExternalMetadata $externalMetadata)
    {
        $this->view->assign('identifierType', $this->determineIdentifierType($externalMetadata->getPublicationIdentifier()));
        $this->view->assign('externalMetadata', $externalMetadata);
    }

    /**
     * @param ExternalMetadata $externalMetadata
     */
    public function createDocumentAction(ExternalMetadata $externalMetadata)
    {
        /** @var  Importer $importer */
        $importer = $this->objectManager->get($externalMetadata->getSource());


        try {
            /** @var Document $newDocument */
            $newDocument = $importer->import($externalMetadata);

            if ($newDocument instanceof Document) {

                $this->documentRepository->add($newDocument);
                $this->persistenceManager->persistAll();

                // index the document
                $this->signalSlotDispatcher->dispatch(
                    AbstractController::class, 'indexDocument', [$newDocument]
                );

                $this->externalMetadataRepository->remove($externalMetadata);

                if ($this->security->getUser()->getUserRole() == Security::ROLE_LIBRARIAN) {
                    $message = LocalizationUtility::translate(
                        'manager.importMetadata.publicationAddedToWorkspace', 'dpf'
                    );
                } else {
                    $message = LocalizationUtility::translate(
                        'manager.importMetadata.publicationAddedToMyPublications', 'dpf'
                    );
                }
                $this->addFlashMessage($message, '', AbstractMessage::OK);

                $this->redirect('showDetails', 'Document', null, ['document' => $newDocument]);

            } else {
                $message = LocalizationUtility::translate(
                    'manager.importMetadata.publicationNotImported', 'dpf'
                );

                $this->addFlashMessage($message, '', AbstractMessage::ERROR);
                $this->redirect('find');
            }

        } catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $e) {
            // A redirect always throws this exception, but in this case, however,
            // redirection is desired and should not lead to an exception handling
        } catch(\Throwable $throwable) {

            $this->logger->error($throwable->getMessage());

            $message = LocalizationUtility::translate(
                'manager.importMetadata.publicationNotImported', 'dpf'
            );

           $this->addFlashMessage($message, '', AbstractMessage::ERROR);
           $this->redirect('find');
        }
    }

    /**
     * Determines whether the identifier is a DOI, ISBN or PMID.
     *
     * @param $identifier
     * @return null|string
     */
    protected function determineIdentifierType($identifier)
    {
        // DOI
        if (strpos($identifier,'10.') === 0) {
            return 'DOI';
        }

        // ISBN
        $length = strlen(str_replace(['-',' '], '', $identifier));

        if ($length === 13) {
            if (strpos($identifier, '978') === 0 ||  strpos($identifier, '979') === 0) {
                return 'ISBN';
            }
        }

        if ($length === 10) {
            return 'ISBN';
        }

        // PMID
        if (is_numeric($identifier) && intval($identifier) == $identifier) {
            if (strlen($identifier) < 10) {
                return 'PMID';
            }
        }

        return null;
    }

    /**
     * Finds a document with the given $identifier in the current users "Workspace" or "My publicstions"
     *
     * @param $identifier
     * @return array
     */
    protected function findDocumentInWorkspace($identifier)
    {
        $bookmarkIdentifiers = [];
        foreach ($this->bookmarkRepository->findByFeUserUid($this->security->getUser()->getUid()) as $bookmark) {
            $bookmarkIdentifiers[] = $bookmark->getDocumentIdentifier();
        }

        if ($this->security->getUser()->getUserRole() == Security::ROLE_LIBRARIAN) {
            // "Workspace" of a librarian
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
        } else {
            // "My publications" of a researcher
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
        }

        $query = $this->queryBuilder->buildQuery(
            1, $workspaceFilter, 0,
            $bookmarkIdentifiers, [], [], null, null,
            'identifier:"'.$identifier.'"'
        );

        try {
            return $this->elasticSearch->search($query, 'object');
        } catch (\Exception $e) {

            $message = LocalizationUtility::translate(
                'manager.importMetadata.searchError', 'dpf'
            );

            $this->addFlashMessage(
                $message, '', AbstractMessage::ERROR
            );
        }

        return [];
    }

    /**
     * @param int $from
     */
    protected function bulkImportedDocumentsAction()
    {
        $this->session->setStoredAction($this->getCurrentAction(), $this->getCurrentController(),
            $this->uriBuilder->getRequest()->getRequestUri()
        );

        $currentPage = null;
        $pagination = $this->getParametersSafely('@widget_0');
        if ($pagination) {
            $currentPage = $pagination['currentPage'];
        } else {
            $currentPage = 1;
        }

        // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($test);

        /** @var BulkImportSessionData $bulkImportSessionData */
        $bulkImportSessionData = $this->session->getBulkImportData();
        $importedIdentifiers = $bulkImportSessionData->getLatestImportIdentifiers();

        $workspaceFilter = [
            'bool' => [
                'must' => [
                    [
                        'terms' => [
                            '_id' => array_values(array_filter($importedIdentifiers))
                        ]
                    ]
                ]
            ]
        ];

        $query = $this->queryBuilder->buildQuery(
            $this->itemsPerPage(),
            $workspaceFilter,
            (empty($currentPage)? 0 : ($currentPage-1) * $this->itemsPerPage())
        );

        try {
            $results = $this->elasticSearch->search($query, 'object');
            $this->view->assign('currentPage', $currentPage);
            $this->view->assign('documentCount', $results['hits']['total']['value']);
            $this->view->assign('documents', $results['hits']['hits']);
            $this->view->assign('itemsPerPage', $this->itemsPerPage());
        } catch (\Throwable $e) {

            $message = LocalizationUtility::translate(
                'manager.importMetadata.searchError', 'dpf'
            );

            $this->addFlashMessage(
                $message, '', AbstractMessage::ERROR
            );
        }
    }

    /**
     * Returns the number of items to be shown per page.
     *
     * @return int
     */
    protected function itemsPerPage()
    {
        /** @var BulkImportSessionData $bulkImportSessionData */
        $bulkImportSessionData = $this->session->getBulkImportData();

        if ($bulkImportSessionData->getItemsPerPage()) {
            return $bulkImportSessionData->getItemsPerPage();
        }

        if ($this->settings['bulkImportPagination']['itemsPerPage']) {
            return $this->settings['bulkImportPagination']['itemsPerPage'];
        }

        return 10;
    }
}
