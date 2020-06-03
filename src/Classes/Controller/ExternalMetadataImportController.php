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
     * Shows the form to find a publication by an identifier
     * @param string $identifier
     * @throws \TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException
     */
    public function findAction($identifier = '')
    {
        $metadata = $this->externalMetadataRepository->findByFeUser(
            $this->security->getUser()->getUid()
        );

        foreach ($metadata as $metadataItem) {
            $this->externalMetadataRepository->remove($metadataItem);
        }

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
                $this->security->getUser()->getUid(),
                $results['hits']['hits'][0]['_id']
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
            } else {
                $message = LocalizationUtility::translate(
                    'manager.importMetadata.publicationNotImported', 'dpf'
                );

                $this->addFlashMessage($message, '', AbstractMessage::ERROR);
            }

        } catch(\Throwable $throwable) {

            $this->logger->error($throwable->getMessage());

            $message = LocalizationUtility::translate(
                'manager.importMetadata.publicationNotImported', 'dpf'
            );

           $this->addFlashMessage($message, '', AbstractMessage::ERROR);
        }

        $this->redirect('find');
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
}
