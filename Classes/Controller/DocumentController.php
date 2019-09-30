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
use EWW\Dpf\Security\DocumentVoter;
use EWW\Dpf\Security\Security;
use EWW\Dpf\Services\Transfer\ElasticsearchRepository;
use EWW\Dpf\Services\Transfer\DocumentTransferManager;
use EWW\Dpf\Services\Transfer\FedoraRepository;
use EWW\Dpf\Services\ProcessNumber\ProcessNumberGenerator;
use EWW\Dpf\Services\Identifier\Urn;
use EWW\Dpf\Services\Email\Notifier;
use EWW\Dpf\Helper\ElasticsearchMapper;
use EWW\Dpf\Exceptions\DPFExceptionInterface;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use TYPO3\CMS\Backend\Exception;

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
     * workflow
     *
     * @var \EWW\Dpf\Domain\Workflow\DocumentWorkflow
     */
    protected $workflow;


    /**
     * action list
     *
     * @param array $stateFilters
     *
     * @return void
     */
    public function listAction($stateFilters = array())
    {
        $this->setSessionData('currentWorkspaceAction','list');

        list($isWorkspace, $documents) = $this->getListViewData($stateFilters);

        if ($this->request->hasArgument('message')) {
            $this->view->assign('message', $this->request->getArgument('message'));
        }

        if ($this->request->hasArgument('errorFiles')) {
            $this->view->assign('errorFiles', $this->request->getArgument('errorFiles'));
        }

        $this->view->assign('currentUser', $this->security->getUser());
        $this->view->assign('isWorkspace', $isWorkspace);
        $this->view->assign('documents', $documents);
    }

    public function listRegisteredAction()
    {
        $this->setSessionData('currentWorkspaceAction','listRegistered');
        list($isWorkspace, $documents) = $this->getListViewData([DocumentWorkflow::STATE_REGISTERED_NONE]);

        if ($this->request->hasArgument('message')) {
            $this->view->assign('message', $this->request->getArgument('message'));
        }

        if ($this->request->hasArgument('errorFiles')) {
            $this->view->assign('errorFiles', $this->request->getArgument('errorFiles'));
        }

        $this->view->assign('isWorkspace', $isWorkspace);
        $this->view->assign('documents', $documents);
    }

    public function listInProgressAction()
    {
        $this->setSessionData('currentWorkspaceAction','listInProgress');

        if ($this->request->hasArgument('message')) {
            $this->view->assign('message', $this->request->getArgument('message'));
        }

        if ($this->request->hasArgument('errorFiles')) {
            $this->view->assign('errorFiles', $this->request->getArgument('errorFiles'));
        }

        list($isWorkspace, $documents) = $this->getListViewData(
            [
                DocumentWorkflow::STATE_IN_PROGRESS_NONE,
                DocumentWorkflow::STATE_IN_PROGRESS_ACTIVE,
                DocumentWorkflow::STATE_IN_PROGRESS_INACTIVE,
                DocumentWorkflow::STATE_IN_PROGRESS_DELETED,
            ]
        );

        $this->view->assign('isWorkspace', $isWorkspace);
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
        $this->authorizationChecker->denyAccessUnlessGranted(DocumentVoter::DISCARD, $document);

        try {
                // remove document from local index
                //$elasticsearchRepository = $this->objectManager->get(ElasticsearchRepository::class);
                // send document to index
                //$elasticsearchRepository->delete($document, "");
                //$this->documentRepository->remove($document);

                if (
                    in_array(
                        $document->getState(),
                        [
                            DocumentWorkflow::STATE_IN_PROGRESS_DELETED,
                            DocumentWorkflow::STATE_IN_PROGRESS_INACTIVE,
                            DocumentWorkflow::STATE_IN_PROGRESS_ACTIVE
                        ]
                    )
                ) {
                    /* todo:
                        ...
                        $documentTransferManager->update($document);
                        ...
                    */
                    $this->workflow->apply($document, DocumentWorkflow::TRANSITION_DISCARD);
                    $this->documentRepository->update($document);
                    $this->documentRepository->remove($document);

                } else {
                    $this->workflow->apply($document, DocumentWorkflow::TRANSITION_DISCARD);
                    $this->documentRepository->update($document);
                }

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
     * action postpone
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function postponeAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $this->authorizationChecker->denyAccessUnlessGranted(DocumentVoter::POSTPONE, $document);

        $documentTransferManager = $this->objectManager->get(DocumentTransferManager::class);
        $remoteRepository = $this->objectManager->get(FedoraRepository::class);
        $documentTransferManager->setRemoteRepository($remoteRepository);

        try {
            if ($document->getState() === DocumentWorkflow::STATE_IN_PROGRESS_ACTIVE) {
                if ($documentTransferManager->delete($document, "inactivate")) {
                    $this->documentRepository->update($document);
                    $this->workflow->apply($document, \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_POSTPONE);
                    $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_postpone.success';
                    $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
                } else {
                    $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_postpone.failure';
                    $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;
                }
            } else {
                $this->documentRepository->update($document);
                $this->workflow->apply($document, \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_POSTPONE);
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_postpone.success';
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
            }
        } catch (\Exception $exception) {

            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_error.unexpected';
            }
        }

        $this->flashMessage($document, $key, $severity);
        $this->redirect('list');
    }


    /**
     * action deleteLocallyConfirm
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function deleteLocallyConfirmAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        throw new \Exception('deleteLocallyConfirmAction');
    }

    /**
     * action deleteLocallyAction
     *
     * @param Document $document
     */
    public function deleteLocallyAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $this->authorizationChecker->denyAccessUnlessGranted(DocumentVoter::DELETE_LOCALLY, $document);

        $this->documentRepository->remove($document);
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
        $this->authorizationChecker->denyAccessUnlessGranted(DocumentVoter::DUPLICATE, $document);

        try {
            /* @var $newDocument \EWW\Dpf\Domain\Model\Document */
            $newDocument = $this->objectManager->get(Document::class);

            $newDocument->setState(DocumentWorkflow::STATE_NEW_NONE);

            $newDocument->setTitle($document->getTitle());
            $newDocument->setAuthors($document->getAuthors());

            $newDocument->setOwner($this->security->getUser()->getUid());

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
        die("deleteAction is obsolete");

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
     * action register
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function registerAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $this->authorizationChecker->denyAccessUnlessGranted(DocumentVoter::REGISTER, $document);

        $this->workflow->apply($document, \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_REGISTER);

        $this->documentRepository->update($document);

        $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_register.success';
        $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
        $this->flashMessage($document, $key, $severity);

        $this->redirect('list');
    }

    /**
     * action showDetails
     *
     * @param Document $document
     * @return void
     */
    public function showDetailsAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $this->authorizationChecker->denyAccessUnlessGranted(DocumentVoter::SHOW_DETAILS, $document);
        $this->view->assign('document', $document);
    }

    /**
     * action cancelListTask
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function cancelListTaskAction()
    {
        $redirectAction = $this->getSessionData('currentWorkspaceAction');
        $redirectAction = empty($redirectAction)? 'defaultAction' : $redirectAction;
        $this->redirect($redirectAction, 'Document', null, array('message' => $message));
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

    /**
     * action uploadFiles
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function uploadFilesAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $this->view->assign('document', $document);
    }


    /**
     * action cause change
     *
     * @param Document $document
     * @return void
     */
    public function causeChangeAction(\EWW\Dpf\Domain\Model\Document $document) {

        $this->authorizationChecker->denyAccessUnlessGranted(DocumentVoter::CAUSE_CHANGE, $document);

        $this->view->assign('document', $document);
    }


    /**
     * action suggest restore
     *
     * @param Document $document
     * @return void
     */
    public function suggestRestoreAction(\EWW\Dpf\Domain\Model\Document $document) {

        $this->authorizationChecker->denyAccessUnlessGranted(DocumentVoter::SUGGEST_RESTORE, $document);

        $this->view->assign('document', $document);
    }


    public function initializeAction()
    {
        $this->authorizationChecker->denyAccessUnlessLoggedIn();

        parent::initializeAction();

        $this->workflow = $this->objectManager->get(DocumentWorkflow::class)->getWorkflow();
    }


    /**
     * get list view data
     *
     * @param array $stateFilters
     *
     * @return array
     */
    protected function getListViewData($stateFilters = array())
    {
        switch ($this->security->getUserRole()) {

            case Security::ROLE_LIBRARIAN:
                $documents = $this->documentRepository->findAllOfALibrarian(
                    $this->security->getUser()->getUid(),
                    $stateFilters
                );
                $isWorkspace = TRUE;
                break;

            case Security::ROLE_RESEARCHER;
                $documents = $this->documentRepository->findAllOfAResearcher(
                    $this->security->getUser()->getUid(),
                    $stateFilters
                );
                break;

            default:
                $documents = NULL;
        }

        return array(
            $isWorkspace,
            $documents
        );
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
