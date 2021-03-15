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

use EWW\Dpf\Domain\Model\CrossRefMetadata;
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Model\PubMedMetadata;
use EWW\Dpf\Security\Security;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use EWW\Dpf\Domain\Model\ExternalMetadata;
use EWW\Dpf\Services\ImportExternalMetadata\Importer;
use EWW\Dpf\Services\ImportExternalMetadata\FileImporter;
use EWW\Dpf\Services\ImportExternalMetadata\CrossRefImporter;
use EWW\Dpf\Services\ImportExternalMetadata\DataCiteImporter;
use EWW\Dpf\Services\ImportExternalMetadata\PubMedImporter;
use EWW\Dpf\Services\ImportExternalMetadata\K10plusImporter;
use EWW\Dpf\Session\BulkImportSessionData;
use EWW\Dpf\Services\ImportExternalMetadata\BibTexFileImporter;
use EWW\Dpf\Services\ImportExternalMetadata\RisWosFileImporter;
use EWW\Dpf\Services\ImportExternalMetadata\RisReader;
use EWW\Dpf\Services\ImportExternalMetadata\PublicationIdentifier;

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
     * metadataGroupRepository
     *
     * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
     * @inject
     */
    protected $metadataGroupRepository;


    /**
     * DocumentController constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param string $crossRefQuery
     * @param string $pubMedQuery
     */
    public function bulkStartAction($crossRefQuery = '', $pubMedQuery = '')
    {
        /** @var BulkImportSessionData $bulkImportSessionData */
        $bulkImportSessionData = $this->session->getBulkImportData();

        $crossRefAuthorSearch = $bulkImportSessionData->getCrossRefSearchField() === 'author';
        $pubMedAuthorSearch = $bulkImportSessionData->getPubMedSearchField() === 'author';

        $this->externalMetadataRepository->clearExternalMetadataByFeUserUid($this->security->getUser()->getUid());
        $this->view->assign('crossRefAuthorSearch', $crossRefAuthorSearch);
        $this->view->assign('pubMedAuthorSearch', $pubMedAuthorSearch);
        $this->view->assign('crossRefQuery', $crossRefQuery);
        $this->view->assign('pubMedQuery', $pubMedQuery);
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

        if ($results) {
            $this->forward(
                'bulkResults',
                null,
                null,
                [
                    'results' => $results,
                    'query' => $query,
                    'importSourceName' => 'Crossref',
                    'currentPage' => $currentPage
                ]
            );
        } else {

            $message = LocalizationUtility::translate(
                'manager.importMetadata.nothingFound', 'dpf'
            );

            $this->addFlashMessage($message, '', AbstractMessage::ERROR);

            $this->forward(
                'bulkStart',
                null,
                null,
                [
                    'crossRefQuery' => $bulkImportSessionData->getCrossRefQuery()
                ]
            );
        }
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
            $bulkImportSessionData->getPubMedSearchField()
        );

        $bulkImportSessionData->setCurrentMetadataItems(($results? $results['items'] : []));
        $this->session->setBulkImportData($bulkImportSessionData);

        if ($results) {
            $this->forward(
                'bulkResults',
                null,
                null,
                [
                    'results' => $results,
                    'query' => $query,
                    'importSourceName' => 'PubMed',
                    'currentPage' => $currentPage
                ]
            );
        } else {

            $message = LocalizationUtility::translate(
                'manager.importMetadata.nothingFound', 'dpf'
            );

            $this->addFlashMessage($message, '', AbstractMessage::ERROR);

            $this->forward(
                'bulkStart',
                null,
                null,
                [
                    'pubMedQuery' => $bulkImportSessionData->getPubMedQuery()
                ]
            );
        }
    }

    /**
     * @param string $importSourceName
     * @param string $query
     * @param array $results
     * @param int $currentPage
     */
    public function bulkResultsAction($importSourceName, $query, $results = null, $currentPage = 1)
    {
        $externalMetadata = $this->externalMetadataRepository->findByFeUser($this->security->getUser()->getUid());
        $checkedPublicationIdentifiers = [];

        /** @var ExternalMetadata $data */
        foreach ($externalMetadata as $data) {
            $checkedPublicationIdentifiers[] = $data->getPublicationIdentifier();
        }

        $this->view->assign('importSourceName', $importSourceName);
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

                // Check if the publication already exists in kitodo
                if ($this->findDocumentInKitodo($externalMetadataItem->getPublicationIdentifier())) {
                    $existingWorkspaceDocument = $this->findDocumentInWorkspace(
                        $externalMetadataItem->getPublicationIdentifier()
                    );
                    if (empty($existingWorkspaceDocument)) {
                        $this->bookmarkRepository->addBookmark(
                            $existingWorkspaceDocument['_id'],
                            $this->security->getUser()->getUid()
                        );
                        $importCounter['bookmarked'] += 1;
                    }

                } else {

                    if (!$this->findDocumentInWorkspace($externalMetadataItem->getPublicationIdentifier())) {
                        /** @var Document $newDocument */
                        $newDocument = $importer->import($externalMetadataItem);

                        if ($newDocument instanceof Document) {
                            $this->documentRepository->add($newDocument);
                            $this->externalMetadataRepository->remove($externalMetadataItem);
                            $importedDocuments[] = $newDocument;
                            $importCounter['imported'] += 1;
                        }
                    }
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
            ['from' => 0, 'importCounter' => $importCounter]);
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

        // Check if the document already exists in the workspace or my publications,
        // if this is the case, nothing will be imported, the find results will be shown again
        // and an error message will be displayed.
        if ($this->findDocumentInWorkspace($identifier)) {
            if ($this->security->getUserRole() == Security::ROLE_LIBRARIAN) {
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

        // Check if the document already exists in kitodo.
        /** @var array $existingDocument */
        if ($existingDocument = $this->findDocumentInKitodo($identifier)) {

            $this->bookmarkRepository->addBookmark(
                $existingDocument['_id'],
                $this->security->getUser()->getUid()
            );

            if ($this->security->getUserRole() == Security::ROLE_LIBRARIAN) {
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
        $identifierType = PublicationIdentifier::determineIdentifierType($identifier);

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
        $this->view->assign('identifierType',
            PublicationIdentifier::determineIdentifierType($externalMetadata->getPublicationIdentifier())
        );
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

                if ($this->security->getUserRole() == Security::ROLE_LIBRARIAN) {
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

        if ($this->security->getUserRole() == Security::ROLE_LIBRARIAN) {
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

            $results =  $this->elasticSearch->search($query, 'object');
            if (is_array($results) && $results['hits']['total']['value'] > 0) {
                return $results['hits']['hits'][0];
            }

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
     * Finds a document with the given $identifier in the kitodo index
     *
     * @param $identifier
     * @return array
     */
    protected function findDocumentInKitodo($identifier) {

        $workspaceFilter = [
            'bool' => [
                'must_not' => [
                    [
                        'term' => [
                            'state' => DocumentWorkflow::STATE_NEW_NONE
                        ]
                    ]
                ]
            ]
        ];

        // Search if the document already exists in kitodo.
        $query = $this->queryBuilder->buildQuery(
            1, $workspaceFilter , 0, [], [], [], null, null, 'identifier:"'.$identifier.'"'
        );
        $results = $this->elasticSearch->search($query, 'object');

        if (is_array($results) && $results['hits']['total']['value'] > 0) {
            return $results['hits']['hits'][0];
        }

        return [];
    }

    /**
     * @param array $importCounter
     */
    public function bulkImportedDocumentsAction($importCounter = ['imported' => 0, 'bookmarked' => 0, 'total' => 0])
    {

        $publicationSingular = LocalizationUtility::translate('manager.importMetadata.publication.singular', 'dpf');
        $publicationPlural = LocalizationUtility::translate('manager.importMetadata.publication.plural', 'dpf');

        if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {
            $messageKey = 'manager.bulkImport.importMessage.libarian';
        } else {
            $messageKey = 'manager.bulkImport.importMessage.researcher';
        }

        $message = LocalizationUtility::translate(
            $messageKey,
            'dpf',
            [
                0 => $importCounter['imported'],
                1 => ($importCounter['imported'] == 1? $publicationSingular : $publicationPlural),
                2 => $importCounter['bookmarked'],
                3 => ($importCounter['bookmarked'] == 1? $publicationSingular : $publicationPlural)
            ]
        );

        if ($importCounter['imported'] > 0 || $importCounter['bookmarked'] > 0) {
            $severity = AbstractMessage::INFO;
        } else {
            $severity = AbstractMessage::WARNING;
        }

        $this->addFlashMessage(
            $message, '', $severity
        );

        if ($importCounter['imported'] > 0 || $importCounter['bookmarked'] > 0) {
            if ($this->security->getUserRole() != Security::ROLE_LIBRARIAN) {
                $importNoteMessage = LocalizationUtility::translate('manager.bulkImport.importNote', 'dpf');
                $this->addFlashMessage(
                    $importNoteMessage, '', AbstractMessage::INFO
                );
            }
        }

        $this->showImportedDocuments($importCounter);
    }

    /**
     *
     */
    protected function showImportedDocuments()
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
            $this->view->assign('currentFisPersId', $this->security->getFisPersId());

            $personGroup = $this->metadataGroupRepository->findPersonGroup();
            $this->view->assign('personGroup', $personGroup->getUid());

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

    /**
     * @param string $error
     */
    public function uploadStartAction($error = '')
    {
        switch ($error) {
            case 'INVALID_FORMAT':
                $message = LocalizationUtility::translate(
                    'manager.uploadImport.invalidFormat', 'dpf'
                );
                $this->addFlashMessage($message, '', AbstractMessage::ERROR);
                break;
            case 'UPLOAD_ERROR':
                $message = LocalizationUtility::translate(
                    'manager.uploadImport.uploadError', 'dpf'
                );
                $this->addFlashMessage($message, '', AbstractMessage::ERROR);
                break;
        }

        $this->externalMetadataRepository->clearExternalMetadataByFeUserUid($this->security->getUser()->getUid());
    }

    /**
     * @param string $fileType (bibtex or riswos)
     * @param array $uploadFile
     */
    public function uploadImportFileAction($fileType, $uploadFile = [])
    {
        $this->externalMetadataRepository->clearExternalMetadataByFeUserUid($this->security->getUser()->getUid());

        $uploadFileUrl = new \EWW\Dpf\Helper\UploadFileUrl;
        $uploadFilePath = PATH_site . $uploadFileUrl->getDirectory() . "/importFile.".md5($this->security->getUser()->getUid());

        if ($uploadFile['error'] === UPLOAD_ERR_OK) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move($uploadFile['tmp_name'], $uploadFilePath);
            //$finfo       = finfo_open(FILEINFO_MIME_TYPE);
            //$contentType = finfo_file($finfo, $uploadFilePath);
            //finfo_close($finfo);
        } elseif ($uploadFile['error'] == UPLOAD_ERR_NO_FILE) {
            $this->redirect('uploadStart');
        } else {
            $this->redirect('uploadStart', null, null, ['error' => 'UPLOAD_ERROR']);
        }

        try {

            if ($fileType == 'bibtex') {
                /** @var FileImporter $fileImporter */
                $fileImporter = $this->objectManager->get(BibTexFileImporter::class);
                $results = $fileImporter->loadFile($uploadFilePath, $this->settings['bibTexMandatoryFields']);
            } elseif ($fileType == 'riswos') {
                /** @var FileImporter $fileImporter */
                $fileImporter = $this->objectManager->get(RisWosFileImporter::class);
                $results = $fileImporter->loadFile($uploadFilePath, $this->settings['riswosMandatoryFields']);
            } else {
                $results = [];
            }

            foreach ($results as $externalMetadata) {
                $this->externalMetadataRepository->add($externalMetadata);
            }

            if ($mandatoryErrors = $fileImporter->getMandatoryErrors()) {
                foreach (
                    $mandatoryErrors as $mandatoryError
                ) {
                    $message = LocalizationUtility::translate(
                        "manager.uploadImport.incompleteData",
                        [
                            $mandatoryError['index'],
                            ($mandatoryError['title'] ? ' (' . $mandatoryError['title'] . ')' : ''),
                            implode(',', $mandatoryError['fields'])
                        ]
                    );
                    $this->addFlashMessage($message, '', AbstractMessage::ERROR);
                }
            } elseif ($results) {
                    $this->redirect(
                        'importUploadedData',
                        null,
                        null,
                        ['uploadFilePath' => $uploadFilePath]
                    );
            } else {
                $this->redirect('uploadStart', null, null, ['error' => 'INVALID_FORMAT']);
            }
        } catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $exception) {
            // A redirect always throws this exception, but in this case, however,
            // redirection is desired and should not lead to an exception handling
        } catch (\RenanBr\BibTexParser\Exception\ParserException $exception) {
            $this->redirect('uploadStart', null, null, ['error' => 'INVALID_FORMAT']);
        }

    }

    /**
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function importUploadedDataAction()
    {
        try {
            $externalMetadata = $this->externalMetadataRepository->findByFeUser($this->security->getUser()->getUid());

            $importedDocuments = [];
            $importedDocumentIdentifiers = [];

            /** @var ExternalMetadata $externalMetadataItem */
            foreach ($externalMetadata as $externalMetadataItem) {

                /** @var  Importer $importer */
                $importer = $this->objectManager->get($externalMetadataItem->getSource());

                /** @var Document $newDocument */
                $newDocument = $importer->import($externalMetadataItem);

                if ($newDocument instanceof Document) {
                    $this->documentRepository->add($newDocument);
                    $this->externalMetadataRepository->remove($externalMetadataItem);
                    $importedDocuments[] = $newDocument;
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
            'uploadedDocuments',
            null,
            null,
            ['from' => 0, 'importCounter' => sizeof($importedDocumentIdentifiers)]);
    }


    /**
     * @param int $importCounter
     */
    public function uploadedDocumentsAction($importCounter = 0)
    {
        $publicationSingular = LocalizationUtility::translate('manager.importMetadata.publication.singular', 'dpf');
        $publicationPlural = LocalizationUtility::translate('manager.importMetadata.publication.plural', 'dpf');

        if ($importCounter != 1) {
            $messageKey = 'manager.uploadImport.importMessage.plural';
        } else {
            $messageKey = 'manager.uploadImport.importMessage.singular';
        }

        $message = LocalizationUtility::translate(
            $messageKey,
            'dpf',
            [
                0 => $importCounter,
                1 => ($importCounter == 1? $publicationSingular : $publicationPlural)
            ]
        );

        if ($importCounter > 0) {
            $severity = AbstractMessage::INFO;
            $this->addFlashMessage($message, '', $severity);

            if ($this->security->getUserRole() != Security::ROLE_LIBRARIAN) {
                $importNoteMessage = LocalizationUtility::translate('manager.uploadImport.importNote', 'dpf');
                $this->addFlashMessage(
                    $importNoteMessage, '', AbstractMessage::INFO
                );
            }
            
            $this->showImportedDocuments($importCounter);
        } else {
            $severity = AbstractMessage::WARNING;
            $this->addFlashMessage($message, '', $severity);
            $this->redirect('uploadStart');
        }
    }
}
