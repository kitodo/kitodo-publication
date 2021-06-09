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
use EWW\Dpf\Domain\Model\Client;
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Model\DocumentType;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Helper\DocumentMapper;
use EWW\Dpf\Helper\InternalFormat;
use EWW\Dpf\Services\ElasticSearch\ElasticSearch;
use EWW\Dpf\Services\ProcessNumber\ProcessNumberGenerator;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
* Backend module user/group action controller
*/
class BackendAdminController extends ActionController
{
    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentRepository = null;

    /**
     * fileRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FileRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $fileRepository = null;

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
     * frontendUserRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FrontendUserRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $frontendUserRepository = null;

    /**
     * clientRepository
     *
     * @var \EWW\Dpf\Domain\Repository\ClientRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $clientRepository = null;

    /**
    * Backend Template Container
    *
    * @var string
    */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * Set up the doc header properly here
     *
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);
        if ($this->actionMethodName == 'indexAction'
            || $this->actionMethodName == 'onlineAction'
            || $this->actionMethodName == 'compareAction') {
            $this->generateMenu();
            $this->registerDocheaderButtons();
            $view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());
        }
        if ($view instanceof BackendTemplateView) {
            $view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        }
    }

    /**
     * @param string $identifier
     */
    public function searchDocumentAction(string $identifier = '')
    {
        $identifier = trim($identifier);
        $this->view->assign('identifier', $identifier);

        if ($identifier) {

            $this->documentRepository->crossClient(true);

            $documents = $this->documentRepository->searchForIdentifier($identifier);

            if ($documents) {

                $clientNames = [];
                foreach ($documents as $document) {
                    /** @var Client $client */
                    $client = $this->clientRepository->findAllByPid($document->getPid())->current();
                    $clientNames[$document->getUid()] = $client->getClient();
                }

                $this->view->assign('documents', $documents);
                $this->view->assign('clientNames', $clientNames);

            } else {
                $this->flashMessage(
                    'nothing_found',
                    'nothing_found_message',
                    AbstractMessage::ERROR,
                    [],
                    [$identifier]
                );
            }
        }
    }

    /**
     * @param Document $document
     * @param string $identifier
     */
    public function chooseNewClientAction(Document $document, string $identifier)
    {
        if ($document) {
            if ($document->isClientChangeable()) {
                /** @var Client $currentClient */
                $currentClient = $this->clientRepository->findAllByPid($document->getPid())->current();

                $allClients = $this->clientRepository->crossClientFindAll(false);

                $documentTypes = [];
                $clients = [];

                /** @var Client $client */
                foreach ($allClients as $client) {
                    if ($client->getUid() != $currentClient->getUid()) {
                        $clients[] = $client;
                        $this->documentTypeRepository->crossClient(true);
                        $documentTypes[$client->getPid()] = $this->documentTypeRepository->findByPid($client->getPid());
                    }
                }

                $this->view->assign('canMoveDocument', $document->isClientChangeable());
                $this->view->assign('document', $document);
                $this->view->assign('clients', $clients);
                $this->view->assign('documentTypes', $documentTypes);
                $this->view->assign('currentClient', $currentClient);
                $this->view->assign('identifier', $identifier);
            } else {
                $this->flashMessage(
                    'change_client_forbidden',
                    'change_client_forbidden_message',
                    AbstractMessage::ERROR,
                    [],
                    [$identifier]
                );

                $this->forward(
                    'searchDocument',
                    null,
                    null,
                    [
                        'identifier' => $identifier
                    ]
                );
            }
        }
    }

    /**
     * @param Document $document
     * @param Client $client
     * @param string $identifier
     * @param DocumentType $documentType
     */
    public function changeClientAction(Document $document, Client $client, string $identifier, DocumentType $documentType = null)
    {
        if ($documentType instanceof DocumentType) {
            if ($document->isClientChangeable()) {

                /** @var Client $currentClient */
                $currentClient = $this->clientRepository->findAllByPid($document->getPid())->current();

                // Move the document to the target client
                // Fixme: How should the creator be dealt with?
                // $document->setCreator();
                $document->setPid($client->getPid());
                $document->setDocumentType($documentType);
                $documentMapper = $this->objectManager->get(DocumentMapper::class);
                $documentMapper->setClientPid($client->getPid());
                $documentForm = $documentMapper->getDocumentForm($document);
                $document = $documentMapper->getDocument($documentForm);
                $internalFormat = new InternalFormat($document->getXmlData(), $client->getPid());
                $internalFormat->setDocumentType($documentType->getName());
                $document->setXmlData($internalFormat->getXml());

                if ($client->getOwnerId()) {
                    /** @var ProcessNumberGenerator $processNumberGenerator */
                    $processNumberGenerator = $this->objectManager->get(ProcessNumberGenerator::class);
                    $processNumber = $processNumberGenerator->getProcessNumber($client->getOwnerId());
                    $document->setProcessNumber($processNumber);
                } else {
                    throw new \Exception('Missing client configuration: "owner id"');
                }

                $this->documentRepository->update($document);

                // Move files to the target client
                $files = $document->getFile();
                foreach ($files as $file) {
                    $file->setPid($client->getPid());
                    $this->fileRepository->update($file);
                }

                // If there are bookmarks, they have to be adjusted, depending on the existence of the related user
                // inside the target client, and either deleted or moved to the target client.
                $this->bookmarkRepository->crossClient(true);
                $bookmarks = $this->bookmarkRepository->findDocumentBookmarks($document);
                /** @var Bookmark $bookmark */
                foreach ($bookmarks as $bookmark) {
                    if ($this->frontendUserRepository->isUserInClient($bookmark->getFeUserUid(), $client->getPid())) {
                        $bookmark->setPid($client->getPid());
                        $this->bookmarkRepository->update($bookmark);
                    } else {
                        $this->bookmarkRepository->remove($bookmark);
                    }
                }

                // Move document into the target search index.
                $elasticSearch = new ElasticSearch($currentClient->getPid());
                $elasticSearch->delete($document->getDocumentIdentifier());
                $targetElasticSearch = new ElasticSearch($client->getPid());
                $targetElasticSearch->index($document);

                $this->flashMessage(
                   'client_changed',
                   'client_changed_message',
                   AbstractMessage::OK,
                   [],
                   [$identifier]
                );
           } else {
               $this->flashMessage(
                   'change_client_forbidden',
                   'change_client_forbidden_message',
                   AbstractMessage::ERROR,
                   [],
                   [$identifier]
               );
           }

           $this->view->assign('currentClient', $client);
           $this->view->assign('document', $document);
       } else {
            $this->flashMessage(
                'missing_document_type',
                'missing_document_type_message',
                AbstractMessage::ERROR,
                [],
                [$identifier]
            );

            $this->forward(
              'chooseNewClient',
              null,
              null,
              [
                  'document' => $document,
                  'identifier' => $identifier
              ]
            );
       }
    }

    /**
     * flashMessage
     *
     * @param string $headerKey
     * @param string $bodyKey
     * @param array $headerArguments
     * @param array $bodyArguments
     * @param int $severity
     * @param bool $storeInSession
     */
    protected function flashMessage(
        string $headerKey,
        string $bodyKey,
        int $severity = AbstractMessage::INFO,
        array $headerArguments,
        array $bodyArguments,
        bool $storeInSession = false
    )
    {
        $messageHeader = LocalizationUtility::translate(
            'LLL:EXT:dpf/Resources/Private/Language/locallang_mod.xlf:admin_module.'.$headerKey,
            'dpf',
            $headerArguments
        );

        $messageBody = LocalizationUtility::translate(
            'LLL:EXT:dpf/Resources/Private/Language/locallang_mod.xlf:admin_module.'.$bodyKey,
            'dpf',
            $bodyArguments
        );

        $this->addFlashMessage($messageBody, $messageHeader, $severity, $storeInSession);
    }

}
