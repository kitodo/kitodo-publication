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
use EWW\Dpf\Domain\Model\LocalDocumentStatus;
use EWW\Dpf\Domain\Model\RemoteDocumentStatus;
use EWW\Dpf\Services\Transfer\ElasticsearchRepository;
use EWW\Dpf\Services\Transfer\DocumentTransferManager;
use EWW\Dpf\Services\Transfer\FedoraRepository;
use EWW\Dpf\Services\ProcessNumber\ProcessNumberGenerator;
use EWW\Dpf\Services\Identifier\Urn;
use EWW\Dpf\Services\Email\Notifier;
use EWW\Dpf\Helper\ElasticsearchMapper;
use EWW\Dpf\Exceptions\DPFExceptionInterface;

/**
 * DocumentController
 */
class DocumentController extends \EWW\Dpf\Controller\AbstractController
{

    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository = null;

    /**
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @inject
     */
    protected $persistenceManager;


    /**
     * action default
     *
     * @return void
     */
    public function defaultAction()
    {
        if ($this->authorizationChecker->isGranted(static::class."::myPublicationsAction") ) {
            $this->forward('myPublications');
        } else {
            $this->forward('list');
        }
    }

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        $documents = $this->documentRepository->findAllFiltered(NULL);

        if ($this->request->hasArgument('message')) {
            $this->view->assign('message', $this->request->getArgument('message'));
        }

        if ($this->request->hasArgument('errorFiles')) {
            $this->view->assign('errorFiles', $this->request->getArgument('errorFiles'));
        }

        $this->view->assign('documents', $documents);
    }

    public function listNewAction()
    {
        $documents = $this->documentRepository->findAllFiltered(
            NULL,
            LocalDocumentStatus::NEW
        );

        $this->view->assign('documents', $documents);
    }

    public function listEditAction()
    {
        $documents = $this->documentRepository->findAllFiltered(
            NULL,
            LocalDocumentStatus::IN_PROGRESS
        );


        //$documents = $this->documentRepository->getInProgressDocuments();
        $this->view->assign('documents', $documents);
    }


    /**
     * action myPublications
     *
     * @return void
     */
    public function myPublicationsAction()
    {
        $ownerFilter = $this->authorizationChecker->getUser()->getUid();
        $documents = $this->documentRepository->findAllFiltered($ownerFilter);

        if ($this->request->hasArgument('message')) {
            $this->view->assign('message', $this->request->getArgument('message'));
        }

        if ($this->request->hasArgument('errorFiles')) {
            $this->view->assign('errorFiles', $this->request->getArgument('errorFiles'));
        }

        $this->view->assign('documents', $documents);
    }


    /**
     * action discardConfirm
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function discardConfirmAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $this->view->assign('document', $document);
    }

    /**
     * action discard
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function discardAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        try {
            // remove document from local index
            $elasticsearchRepository = $this->objectManager->get(ElasticsearchRepository::class);
            // send document to index
            $elasticsearchRepository->delete($document, "");

            $this->documentRepository->remove($document);

            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_discard.success';
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;

        } catch (\Exception $exception) {

            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_discard.failure';
            }
        }

        $this->flashMessage($document, $key, $severity);
        $this->redirect('list');
    }

    /**
     * action duplicate
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function duplicateAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        try {
            /* @var $newDocument \EWW\Dpf\Domain\Model\Document */
            $newDocument = $this->objectManager->get(Document::class);

            $newDocument->setLocalStatus(LocalDocumentStatus::NEW);
            $newDocument->setRemoteStatus(NULL);

            $newDocument->setTitle($document->getTitle());
            $newDocument->setAuthors($document->getAuthors());

            $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());
            $mods->clearAllUrn();
            $newDocument->setXmlData($mods->getModsXml());

            $newDocument->setDocumentType($document->getDocumentType());

            $processNumberGenerator = $this->objectManager->get(ProcessNumberGenerator::class);
            $processNumber = $processNumberGenerator->getProcessNumber();
            $newDocument->setProcessNumber($processNumber);

            $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData());
            $slub->setProcessNumber($processNumber);
            $newDocument->setSlubInfoData($slub->getSlubXml());

            // send document to index
            $elasticsearchRepository = $this->objectManager->get(ElasticsearchRepository::class);

            $elasticsearchMapper = $this->objectManager->get(ElasticsearchMapper::class);
            $json = $elasticsearchMapper->getElasticsearchJson($newDocument);

            $elasticsearchRepository->add($newDocument, $json);

            $this->documentRepository->add($newDocument);

            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_duplicate.success';
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
        } catch (\Exception $exception) {

            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_duplicate.failure';
            }
        }

        $this->flashMessage($document, $key, $severity);

        $this->redirect('list');
    }

    /**
     * action releaseConfirm
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param string $releaseType
     * @return void
     */
    public function releaseConfirmAction(\EWW\Dpf\Domain\Model\Document $document, $releaseType)
    {
        $this->view->assign('releaseType', $releaseType);
        $this->view->assign('document', $document);
    }

    /**
     * action release
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function releaseAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        try {
            // generate URN if needed
            $qucosaId = $document->getObjectIdentifier();
            if (empty($qucosaId)) {
                $qucosaId = $document->getReservedObjectIdentifier();
            }
            if (empty($qucosaId)) {
                $documentTransferManager = $this->objectManager->get(DocumentTransferManager::class);
                $remoteRepository        = $this->objectManager->get(FedoraRepository::class);
                $documentTransferManager->setRemoteRepository($remoteRepository);
                $qucosaId = $documentTransferManager->getNextDocumentId();
                $document->setReservedObjectIdentifier($qucosaId);
            }

            $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());
            if (!$mods->hasQucosaUrn()) {
                $urnService = $this->objectManager->get(Urn::class);
                $urn        = $urnService->getUrn($qucosaId);
                $mods->addQucosaUrn($urn);
                $document->setXmlData($mods->getModsXml());
            }

            $documentTransferManager = $this->objectManager->get(DocumentTransferManager::class);
            $remoteRepository        = $this->objectManager->get(FedoraRepository::class);
            $documentTransferManager->setRemoteRepository($remoteRepository);

            $objectIdentifier = $document->getObjectIdentifier();

            if (empty($objectIdentifier)) {

                // Document is not in the fedora repository.

                if ($documentTransferManager->ingest($document)) {
                    $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_ingest.success';
                    $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
                    $notifier = $this->objectManager->get(Notifier::class);
                    $notifier->sendIngestNotification($document);
                }
            } else {

                // Document needs to be updated.

                if ($documentTransferManager->update($document)) {
                    $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.success';
                    $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
                }
            }
        } catch (\Exception $exception) {
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }
        }

        $this->flashMessage($document, $key, $severity);

        $this->redirect('list');
    }

    /**
     * action restoreConfirm
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function restoreConfirmAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $this->view->assign('document', $document);
    }

    /**
     * action restore
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function restoreAction(\EWW\Dpf\Domain\Model\Document $document)
    {

        $documentTransferManager = $this->objectManager->get(DocumentTransferManager::class);
        $remoteRepository        = $this->objectManager->get(FedoraRepository::class);
        $documentTransferManager->setRemoteRepository($remoteRepository);

        try {
            if ($documentTransferManager->delete($document, "inactivate")) {
                $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_restore.success';
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
            }
        } catch (\Exception $exception) {
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }
        }

        $this->flashMessage($document, $key, $severity);

        $this->redirect('list');
    }

    /**
     * action deleteConfirm
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function deleteConfirmAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $this->view->assign('document', $document);
    }

    /**
     * action delete
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function deleteAction(\EWW\Dpf\Domain\Model\Document $document)
    {

        $documentTransferManager = $this->objectManager->get(DocumentTransferManager::class);
        $remoteRepository        = $this->objectManager->get(FedoraRepository::class);
        $documentTransferManager->setRemoteRepository($remoteRepository);

        try {
            if ($documentTransferManager->delete($document, "")) {
                $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_delete.success';
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
            }
        } catch (\Exception $exception) {
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }
        }

        $this->flashMessage($document, $key, $severity);

        $this->redirect('list');
    }

    /**
     * action activateConfirm
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function activateConfirmAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $this->view->assign('document', $document);
    }

    /**
     * action activate
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function activateAction(\EWW\Dpf\Domain\Model\Document $document)
    {

        $documentTransferManager = $this->objectManager->get(DocumentTransferManager::class);
        $remoteRepository        = $this->objectManager->get(FedoraRepository::class);
        $documentTransferManager->setRemoteRepository($remoteRepository);

        try {
            if ($documentTransferManager->delete($document, "revert")) {
                $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_activate.success';
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
            }
        } catch (\Exception $exception) {
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }
        }

        $this->flashMessage($document, $key, $severity);

        $this->redirect('list');
    }

    /**
     * action inactivateConfirm
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function inactivateConfirmAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $this->view->assign('document', $document);
    }

    /**
     * action inactivate
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function inactivateAction(\EWW\Dpf\Domain\Model\Document $document)
    {

        $documentTransferManager = $this->objectManager->get(DocumentTransferManager::class);
        $remoteRepository        = $this->objectManager->get(FedoraRepository::class);
        $documentTransferManager->setRemoteRepository($remoteRepository);

        try {
            if ($documentTransferManager->delete($document, "inactivate")) {
                $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_inactivate.success';
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
            }
        } catch (\Exception $exception) {
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }
        }

        $this->flashMessage($document, $key, $severity);

        $this->redirect('list');
    }

    protected function getStoragePID()
    {
        return $this->settings['persistence']['classes']['EWW\Dpf\Domain\Model\Document']['newRecordStoragePid'];
    }

    /**
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param string $key
     * @param string $severity
     * @param string $defaultMessage
     */
    protected function flashMessage(\EWW\Dpf\Domain\Model\Document $document, $key, $severity, $defaultMessage = "")
    {

        // Show success or failure of the action in a flash message
        $args[] = $document->getTitle();
        $args[] = $document->getObjectIdentifier();

        $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf', $args);
        $message = empty($message) ? $defaultMessage : $message;

        $this->addFlashMessage(
            $message,
            '',
            $severity,
            true
        );

    }
}
