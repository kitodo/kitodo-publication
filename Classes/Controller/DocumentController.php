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
use EWW\Dpf\Domain\Model\DocumentType;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Exceptions\DPFExceptionInterface;
use EWW\Dpf\Helper\DocumentMapper;
use EWW\Dpf\Security\DocumentVoter;
use EWW\Dpf\Security\Security;
use EWW\Dpf\Services\Api\InternalFormat;
use EWW\Dpf\Services\Email\Notifier;
use EWW\Dpf\Services\ProcessNumber\ProcessNumberGenerator;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;


/**
 * DocumentController
 */
class DocumentController extends AbstractController
{

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
     * inputOptionListRepository
     *
     * @var \EWW\Dpf\Domain\Repository\InputOptionListRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $inputOptionListRepository = null;

    /**
     * persistence manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $persistenceManager;

    /**
     * editingLockService
     *
     * @var \EWW\Dpf\Services\Document\EditingLockService
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $editingLockService = null;

    /**
     * documentValidator
     *
     * @var \EWW\Dpf\Helper\DocumentValidator
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentValidator;

    /**
     * depositLicenseLogRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DepositLicenseLogRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $depositLicenseLogRepository = null;

    /**
     * workflow
     *
     * @var \EWW\Dpf\Domain\Workflow\DocumentWorkflow
     */
    protected $workflow;

    /**
     * documentStorage
     *
     * @var \EWW\Dpf\Services\Storage\DocumentStorage
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentStorage = null;

    /**
     * fileRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FileRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $fileRepository = null;

    /**
     * frontendUserRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FrontendUserRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $frontendUserRepository = null;

    /**
     * documentManager
     *
     * @var \EWW\Dpf\Services\Document\DocumentManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentManager = null;

    /**
     * bookmarkRepository
     *
     * @var \EWW\Dpf\Domain\Repository\BookmarkRepository
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $bookmarkRepository = null;

    /**
     * action logout of the backoffice
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function logoutAction()
    {
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $uri = $cObj->typolink_URL([
            'parameter' => $this->settings['loginPage'],
            //'linkAccessRestrictedPages' => 1,
            'forceAbsoluteUrl' => 1,
            'additionalParams' => GeneralUtility::implodeArrayForUrl(NULL, ['logintype' => 'logout'])
        ]);

        $this->redirectToUri($uri);
    }

    public function listSuggestionsAction()
    {
        $this->session->setStoredAction($this->getCurrentAction(), $this->getCurrentController());

        $documents = NULL;
        $isWorkspace = $this->security->getUserRole() === Security::ROLE_LIBRARIAN;

        if (
            $this->security->getUserRole() == Security::ROLE_LIBRARIAN
        ) {
            $documents = $this->documentRepository->findAllDocumentSuggestions(
                $this->security->getUserRole(),
                $this->security->getUser()->getUid()
            );
        }

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

    /**
     * @param Document $document
     * @param array $acceptedChanges
     * @param string $acceptMode
     */
    public function acceptSuggestionAction(Document $document, array $acceptedChanges = null, string $acceptMode = null)
    {

        /** @var DocumentMapper $documentMapper */
        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        $linkedUid = $document->getLinkedUid();
        $newDocumentForm = $documentMapper->getDocumentForm($document);

        /** @var Document $doc */
        $originDocument = $this->documentManager->read($linkedUid);

        if ($document->getDocumentType()->getUid() !== $originDocument->getDocumentType()->getUid()) {
            $originDocument->setDocumentType($document->getDocumentType());
        }

        $linkedDocumentForm = $documentMapper->getDocumentForm($originDocument);

        $documentChanges = $linkedDocumentForm->diff($newDocumentForm);


        $acceptRestore = false;

        if ($acceptMode === 'ACCEPT_ALL') {
            $documentChanges->acceptAll();
            $acceptRestore = true;
        } elseif ($acceptMode === 'ACCEPT_SELECTION') {
            if (is_array($acceptedChanges)) {
                foreach ($acceptedChanges['changes'] as $groupId => $groupChange) {
                    if ($groupChange['accept']) {
                        $documentChanges->acceptGroup($groupId);
                        if (array_key_exists('fieldChanges', $groupChange)) {
                            foreach ($groupChange['fieldChanges'] as $fieldId => $fieldChange) {
                                if ($fieldChange['accept']) {
                                    $documentChanges->acceptField($groupId, $fieldId);
                                }
                            }
                        }
                    }
                }

                if (array_key_exists('acceptRestore', $acceptedChanges)) {
                    $acceptRestore = $acceptedChanges['acceptRestore'] == 1;
                }
            }
        }

        if ($acceptMode === 'ACCEPT_ALL' || $acceptMode === 'ACCEPT_SELECTION') {

            $linkedDocumentForm->applyChanges($documentChanges);

            /** @var \EWW\Dpf\Domain\Model\Document $updateDocument */
            $originDocument = $documentMapper->getDocument($linkedDocumentForm);

            if ($document->getRemoteState() != DocumentWorkflow::REMOTE_STATE_NONE) {
                if ($document->getLocalState() != DocumentWorkflow::LOCAL_STATE_IN_PROGRESS) {
                    $originDocument->setState(
                        DocumentWorkflow::constructState(
                            DocumentWorkflow::LOCAL_STATE_IN_PROGRESS,
                            $document->getRemoteState()
                        )
                    );
                    $this->addFlashMessage(
                        LocalizationUtility::translate("message.suggestion_accepted.new_workingcopy_info", "dpf"),
                        '',
                        AbstractMessage::INFO
                    );
                }
            }

            if ($acceptRestore && $document->getTransferStatus() == 'RESTORE') {
                if ($originDocument->getObjectIdentifier()) {
                    $originDocument->setState(DocumentWorkflow::STATE_IN_PROGRESS_ACTIVE);
                } else {
                    $originDocument->setState(DocumentWorkflow::STATE_IN_PROGRESS_NONE);
                }
            }

            // Document type changed?
            if ($document->getDocumentType()->getUid() != $originDocument->getDocumentType()->getUid()) {
                $originDocument->setDocumentType($document->getDocumentType());
                $internalFormat = new \EWW\Dpf\Services\Api\InternalFormat($originDocument->getXmlData());
                $internalFormat->setDocumentType($document->getDocumentType()->getName());
                $originDocument->setXmlData($internalFormat->getXml());

                /** @var DocumentMapper $documentMapper */
                $documentMapper = $this->objectManager->get(DocumentMapper::class);
                // Adjusting the document data according to the new document type
                $documentForm = $documentMapper->getDocumentForm($originDocument);
                $originDocument = $documentMapper->getDocument($documentForm);
            }

            $this->documentRepository->update($originDocument);
            $this->documentRepository->remove($document);

            // Notify assigned users
            /** @var Notifier $notifier */
            $notifier = $this->objectManager->get(Notifier::class);

            $recipients = $this->documentManager->getUpdateNotificationRecipients($originDocument);
            $notifier->sendMyPublicationUpdateNotification($originDocument, $recipients);

            $recipients = $this->documentManager->getNewPublicationNotificationRecipients($originDocument);
            $notifier->sendMyPublicationNewNotification($originDocument, $recipients);

            $notifier->sendSuggestionAcceptNotification($originDocument);

            // index the document
            $this->signalSlotDispatcher->dispatch(
                AbstractController::class,
                'indexDocument',
                [$originDocument]
            );

            // redirect to document
            $this->redirect('showDetails', 'Document', null, ['document' => $originDocument]);
        } else {
            throw new \Exception('Accept suggestion: Invalid accept mode.');
        }

        $this->redirectToDocumentList();
    }


    public function showSuggestionDetailsAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $this->authorizationChecker->denyAccessUnlessGranted(DocumentVoter::SHOW_DETAILS, $document);

        /** @var DocumentMapper $documentMapper */
        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        $linkedUid = $document->getLinkedUid();

        /** @var Document $doc */
        $linkedDocument = $this->documentManager->read($linkedUid);

        $newDocumentForm = $documentMapper->getDocumentForm($document);

        $linkedDocumentForm = $documentMapper->getDocumentForm($linkedDocument);

        $documentChangesLostData = null;

        if ($document->getDocumentType()->getUid() !== $linkedDocument->getDocumentType()->getUid()) {

            $this->view->assign('documentTypeChange', 1);
            $this->view->assign('oldDocumentType', $linkedDocument->getDocumentType()->getDisplayName());
            $this->view->assign('newDocumentType', $document->getDocumentType()->getDisplayName());

            $linkedDocumentEmbargoGroups     = $linkedDocument->getDocumentType()->getGroupsWithEmbargoField();
            $suggestionDocumentEmbargoGroups = $document->getDocumentType()->getGroupsWithEmbargoField();
            $this->view->assign('embargoMightBeLost',
                array_diff($linkedDocumentEmbargoGroups, $suggestionDocumentEmbargoGroups)
                !== array_diff($suggestionDocumentEmbargoGroups, $linkedDocumentEmbargoGroups)
            );

            $linkedDocumentFormOrig = $documentMapper->getDocumentForm($linkedDocument);
            $documentChangesLostData = $linkedDocumentForm->diff($newDocumentForm);

            $linkedDocument->setDocumentType($document->getDocumentType());
            $linkedDocumentForm = $documentMapper->getDocumentForm($linkedDocument);
        }

        $documentChanges = $linkedDocumentForm->diff($newDocumentForm);

        $user = $this->frontendUserRepository->findOneByUid($document->getCreator());
        if ($user) {
            $usernameString = $user->getUsername();
        }

        $this->view->assign('documentCreator', $usernameString);

        $this->view->assign('originalDocument', $linkedDocument);
        $this->view->assign('document', $document);
        $this->view->assign('documentChanges', $documentChanges);
        $this->view->assign('documentChangesLostData', $documentChangesLostData);
    }

    public function removeControlCharacterFromString($string)
    {
        return preg_replace('/\p{C}+/u', "", $string);
    }

    /**
     * action discard
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param string $reason
     * @param int $tstamp
     * @return void
     */
    public function discardAction(Document $document, string $reason = null, int $tstamp = null)
    {
        // FIXME: Why is the parameter tstamp not used?

        if (!$this->authorizationChecker->isGranted(DocumentVoter::DISCARD, $document)) {
            if (
                $this->editingLockService->isLocked(
                    $document->getDocumentIdentifier(),
                    $this->security->getUser()->getUid()
                )
            ) {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_discard.failureBlocked';
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_discard.accessDenied';
            }
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        $this->updateDocument($document, DocumentWorkflow::TRANSITION_DISCARD, $reason);
    }

    /**
     * action postpone
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param string $reason
     * @param int $tstamp
     * @return void
     */
    public function postponeAction(Document $document, string $reason = null, int $tstamp = null)
    {
        // FIXME: Why is the parameter tstamp not used?

        if (!$this->authorizationChecker->isGranted(DocumentVoter::POSTPONE, $document)) {
            if (
                $this->editingLockService->isLocked(
                    $document->getDocumentIdentifier(),
                    $this->security->getUser()->getUid()
                )
            ) {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_postpone.failureBlocked';
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_postpone.accessDenied';
            }
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        $this->updateDocument($document, DocumentWorkflow::TRANSITION_POSTPONE, $reason);
    }


    /**
     * action change document type
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param int $documentTypeUid
     * @return void
     */
    public function changeDocumentTypeAction(\EWW\Dpf\Domain\Model\Document $document, $documentTypeUid = 0)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::UPDATE, $document)) {
            if (
                $this->editingLockService->isLocked(
                    $document->getDocumentIdentifier(),
                    $this->security->getUser()->getUid()
                )
            ) {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.failureBlocked';
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.accessDenied';
            }
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }


        $documentType = $this->documentTypeRepository->findByUid($documentTypeUid);

        if ($documentType instanceof DocumentType) {
            $document->setDocumentType($documentType);

            $internalFormat = new \EWW\Dpf\Services\Api\InternalFormat($document->getXmlData());
            $internalFormat->setDocumentType($documentType->getName());
            $document->setXmlData($internalFormat->getXml());

            /** @var DocumentMapper $documentMapper */
            $documentMapper = $this->objectManager->get(DocumentMapper::class);
            // Adjusting the document data according to the new document type
            $documentForm = $documentMapper->getDocumentForm($document);
            $document = $documentMapper->getDocument($documentForm);

            $this->updateDocument($document, '', null);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
        } else {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.failure';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }
    }

    /**
     * action deleteLocallySuggestionAction
     *
     * @param Document $document
     * @param integer $tstamp
     * @param string $reason
     * @return void
     */
    public function deleteLocallySuggestionAction(\EWW\Dpf\Domain\Model\Document $document, $tstamp, $reason = "")
    {
        $this->redirect(
            'deleteLocally',
            'Document',
            null,
            [
                'document' => $document,
                'tstamp' => $tstamp,
                'reason' => $reason
            ]
        );
    }


    /**
     * action deleteLocallyAction
     *
     * @param Document $document
     * @param integer $tstamp
     * @param string $reason
     * @return void
     */
    public function deleteLocallyAction(\EWW\Dpf\Domain\Model\Document $document, $tstamp, $reason = "")
    {
        if (empty($document->getObjectIdentifier()) || $document->isSuggestion()) {
            $voterAttribute = DocumentVoter::DELETE_LOCALLY;
        } else {
            $voterAttribute = DocumentVoter::DELETE_WORKING_COPY;
        }

        if (!$this->authorizationChecker->isGranted($voterAttribute, $document)) {
            if (
                $this->editingLockService->isLocked(
                    $document->getDocumentIdentifier(),
                    $this->security->getUser()->getUid()
                )
            ) {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_deleteLocally.failureBlocked';
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_deleteLocally.accessDenied';
            }
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        if ($tstamp !== $document->getTstamp()) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_deleteLocally.failureNewVersion';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        // remove document
        $this->documentRepository->remove($document);

        // remove pending suggestions for the removed document
        // unless there is a corresponding remote copy
        if ($document->getRemoteState() == DocumentWorkflow::REMOTE_STATE_NONE) {
            $suggestions = $this->documentRepository->findByLinkedUid($document->getProcessNumber());
            foreach ($suggestions as $suggestion) {
                $this->documentRepository->remove($suggestion);
            }
        }

        // if the document was a suggestion, notify about the decline
        if ($document->isSuggestion()) {
            /** @var Notifier $notifier */
            $notifier = $this->objectManager->get(Notifier::class);
            $notifier->sendSuggestionDeclineNotification($document, $reason);
        } else {
            // otherwise notify about regular document deletion
            /** @var Notifier $notifier */
            $notifier = $this->objectManager->get(Notifier::class);
            $notifier->sendDocumentDeletedNotification($document, $reason);

            // remove any bookmarks for working copies
            if ($document->isWorkingCopy()) {
                $bookmarkRemoved = $this->bookmarkRepository->removeBookmark($document, $this->security->getUser()->getUid());

                // prepare flash message about bookmark removal for working copies
                if ($bookmarkRemoved) {
                    $this->addFlashMessage(
                        LocalizationUtility::translate("manager.workspace.bookmarkRemoved.singular", "dpf"),
                        '',
                        AbstractMessage::INFO
                    );
                }
            }

            // trigger reindex of remote document after removing
            // working copies and temporary copies
            if ($document->isWorkingCopy() || $document->isTemporaryCopy()) {
                $this->persistenceManager->persistAll();
                $document = $this->documentManager->read($document->getDocumentIdentifier());
                // index the document
                $this->signalSlotDispatcher->dispatch(
                    \EWW\Dpf\Controller\AbstractController::class,
                    'indexDocument',
                    [$document]
                );
                $this->documentRepository->remove($document);
            } else {
                // request delete document from index
                $this->signalSlotDispatcher->dispatch(
                    \EWW\Dpf\Controller\AbstractController::class,
                    'deleteDocumentFromIndex',
                    [$document->getDocumentIdentifier()]
                );
            }
        }

        // prepare success message
        $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_deleteLocally.success';
        $this->flashMessage($document, $key, AbstractMessage::OK);

        $this->redirectToDocumentList();
    }


    /**
     * @param Document $document
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function duplicateAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::DUPLICATE, $document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_duplicate.accessDenied';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        /* @var $newDocument \EWW\Dpf\Domain\Model\Document */
        $newDocument = $this->objectManager->get(Document::class);

        $newDocument->setState(DocumentWorkflow::STATE_NEW_NONE);

        $copyTitle = LocalizationUtility::translate("manager.workspace.title.copy", "dpf") . $document->getTitle();

        $newDocument->setTitle($copyTitle);

        $newDocument->setCreator($this->security->getUser()->getUid());

        $newDocument->setDocumentType($document->getDocumentType());

        $processNumberGenerator = $this->objectManager->get(ProcessNumberGenerator::class);
        $processNumber = $processNumberGenerator->getProcessNumber();
        $newDocument->setProcessNumber($processNumber);

        $internalFormat = new \EWW\Dpf\Services\Api\InternalFormat($document->getXmlData());
        $internalFormat->clearAllUrn();
        $internalFormat->setDateIssued('');
        $internalFormat->setTitle($copyTitle);
        $internalFormat->setProcessNumber($processNumber);

        $newDocument->setXmlData($internalFormat->getXml());

        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        /** @var $documentForm \EWW\Dpf\Domain\Model\DocumentForm */
        $newDocumentForm = $documentMapper->getDocumentForm($newDocument);

        $this->forward(
            'new',
            'DocumentFormBackoffice',
            NULL,
            ['newDocumentForm' => $newDocumentForm, 'returnDocumentId' => $document->getUid()]
        );
    }

    /**
     * releasePublishAction
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param integer $tstamp
     * @return void
     */
    public function releasePublishAction(\EWW\Dpf\Domain\Model\Document $document, $tstamp = null)
    {
        // FIXME: Why is the $tstamp parameter not used ?

        if (!$this->authorizationChecker->isGranted(DocumentVoter::RELEASE_PUBLISH, $document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_ingest.accessDenied';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        if (!$this->documentValidator->validate($document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_release.missingValues';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
        } else {
            $this->updateDocument($document, DocumentWorkflow::TRANSITION_RELEASE_PUBLISH, null);
        }

        /** @var Notifier $notifier */
        $notifier = $this->objectManager->get(Notifier::class);
        $notifier->sendReleasePublishNotification($document);

        $notifier->sendReleasePublishNotificationToSubmitter($document);
    }

    /**
     * releaseActivateAction
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @param integer $tstamp
     * @return void
     */
    public function releaseActivateAction(\EWW\Dpf\Domain\Model\Document $document, $tstamp = null)
    {
        // FIXME: Why is the $tstamp parameter not used ?

        if (!$this->authorizationChecker->isGranted(DocumentVoter::RELEASE_ACTIVATE, $document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_activate.accessDenied';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        if (!$this->documentValidator->validate($document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_release.missingValues';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
        } else {
            $this->updateDocument($document, DocumentWorkflow::TRANSITION_RELEASE_ACTIVATE, null);
        }
    }

    /**
     * action register
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function registerAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::REGISTER, $document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_register.accessDenied';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
        }

        if (!$this->documentValidator->validate($document, false)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_register.missingValues';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
        }

        $this->workflow->apply($document, \EWW\Dpf\Domain\Workflow\DocumentWorkflow::TRANSITION_REGISTER);
        $this->documentRepository->update($document);


        if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {
            $this->bookmarkRepository->addBookmark($document, $this->security->getUser()->getUid());
        }

        // admin register notification
        $notifier = $this->objectManager->get(Notifier::class);
        $notifier->sendRegisterNotification($document);

        // submitter register notification
        $notifier->sendRegisterNotificationToSubmitter($document);

        // index the document
        $this->signalSlotDispatcher->dispatch(\EWW\Dpf\Controller\AbstractController::class, 'indexDocument', [$document]);

        // document updated notification
        $recipients = $this->documentManager->getUpdateNotificationRecipients($document);
        $notifier->sendMyPublicationUpdateNotification($document, $recipients);

        $recipients = $this->documentManager->getNewPublicationNotificationRecipients($document);
        $notifier->sendMyPublicationNewNotification($document, $recipients);

        $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_register.success';
        $this->flashMessage($document, $key, AbstractMessage::OK);
        $this->redirect('showDetails', 'Document', null, ['document' => $document]);
    }

    /**
     * action showDetails
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function showDetailsAction(Document $document)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::SHOW_DETAILS, $document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_showDetails.accessDenied';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirectToDocumentList();
        }

        $this->session->setCurrenDocument($document);

        $postponeOptions = $this->inputOptionListRepository->findOneByName($this->settings['postponeOptionListName']);
        if ($postponeOptions) {
            $this->view->assign('postponeOptions', $postponeOptions->getInputOptions());
        }

        $discardOptions = $this->inputOptionListRepository->findOneByName($this->settings['discardOptionListName']);
        if ($discardOptions) {
            $this->view->assign('discardOptions', $discardOptions->getInputOptions());
        }

        $mapper = $this->objectManager->get(DocumentMapper::class);
        $documentForm = $mapper->getDocumentForm($document, false);

        $documentTypes = [0 => ''];
        foreach ($this->documentTypeRepository->getDocumentTypesAlphabetically() as $documentType) {
            if (!$documentType->isHiddenInList()) {
                $documentTypes[$documentType->getUid()] = $documentType->getDisplayName();
            }
        }

        $suggestion = $this->documentRepository->findSuggestionByDocument($document);
        $this->view->assign('suggestion', $suggestion);

        $this->view->assign('documentTypes', $documentTypes);

        $this->view->assign('documentForm', $documentForm);

        $this->view->assign('document', $document);
    }


    public function cancelListTaskAction()
    {
        $this->redirectToDocumentList();
    }

    /**
     * action suggest restore
     *
     * @param Document $document
     * @return void
     */
    public function suggestRestoreAction(\EWW\Dpf\Domain\Model\Document $document)
    {

        if (!$this->authorizationChecker->isGranted(DocumentVoter::SUGGEST_RESTORE, $document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_suggestRestore.accessDenied';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        $this->view->assign('document', $document);
    }

    /**
     * @param Document $document
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function suggestModificationAction(\EWW\Dpf\Domain\Model\Document $document)
    {

        $this->authorizationChecker->denyAccessUnlessGranted(DocumentVoter::SUGGEST_MODIFICATION, $document);

        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        /* @var $newDocument \EWW\Dpf\Domain\Model\Document */
        $documentForm = $documentMapper->getDocumentForm($document);

        $this->view->assign('suggestMod', true);
        $this->forward('edit', 'DocumentFormBackoffice', NULL, ['documentForm' => $documentForm, 'suggestMod' => true]);
    }


    /**
     * Translate query parameter `document` into a domain model Document object before
     * routing to the called action.
     */
    public function initializeAction()
    {
        $this->authorizationChecker->denyAccessUnlessLoggedIn();

        parent::initializeAction();

        $this->workflow = $this->objectManager->get(DocumentWorkflow::class)->getWorkflow();

        if ($this->request->hasArgument('document')) {
            $documentParam = $this->request->getArgument('document');

            if (is_array($documentParam) && key_exists("__identity", $documentParam)) {
                $documentIdentifier = $documentParam["__identity"];
            } else {
                $documentIdentifier = $documentParam;
            }

            $document = $this->documentManager->read($documentIdentifier, $this->security->getUser()->getUID());

            if (!$document) {
                $this->redirectToDocumentList();
            }

            $this->request->setArgument('document', $document);
        }
    }

    /**
     * Redirect to the current document list.
     *
     * @param null $message
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    protected function redirectToDocumentList($message = null)
    {
        list($action, $controller, $redirectUri) = $this->session->getStoredAction();

        if ($redirectUri) {
            $this->redirectToUri($redirectUri);
        } else {
            $this->redirect($action, $controller);
        }
    }

    /**
     * Gets the storage PID of the current client
     *
     * @return mixed
     */
    protected function getStoragePID()
    {
        return $this->settings['persistence']['classes']['EWW\Dpf\Domain\Model\Document']['newRecordStoragePid'];
    }

    /**
     * Updates the document in combination with a state transition.
     *
     * @param Document $document
     * @param $workflowTransition
     * @param string $reason
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    protected function updateDocument(\EWW\Dpf\Domain\Model\Document $document, $workflowTransition, $reason)
    {
        switch ($workflowTransition) {
            case DocumentWorkflow::TRANSITION_DISCARD:
                $messageKeyPart = 'document_discard';
                break;
            case DocumentWorkflow::TRANSITION_POSTPONE:
                $messageKeyPart = 'document_postpone';
                break;
            case DocumentWorkflow::TRANSITION_RELEASE_ACTIVATE:
                $messageKeyPart = 'document_activate';
                break;
            case DocumentWorkflow::TRANSITION_RELEASE_PUBLISH:
                $messageKeyPart = 'document_ingest';
                break;
            default:
                $messageKeyPart = "document_update";
                break;
        }

        try {
            if ($reason) {
                $timezone = new \DateTimeZone($this->settings['timezone']);
                $timeStamp = (new \DateTime('now', $timezone))->format("d.m.Y H:i:s");

                if ($workflowTransition == DocumentWorkflow::TRANSITION_DISCARD) {
                    $note = LocalizationUtility::translate(
                        "manager.document.discard.note",
                        "dpf",
                        [$timeStamp, $reason]
                    );
                } elseif ($workflowTransition == DocumentWorkflow::TRANSITION_POSTPONE) {
                    $note = LocalizationUtility::translate(
                        "manager.document.postpone.note",
                        "dpf",
                        [$timeStamp, $reason]
                    );
                }

                $internalFormat = new \EWW\Dpf\Services\Api\InternalFormat($document->getXmlData());
                $internalFormat->addNote($note);
                $document->setXmlData($internalFormat->getXml());
            }

            if ($this->documentManager->update($document, $workflowTransition)) {

                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:' . $messageKeyPart . '.success';
                $this->flashMessage($document, $key, AbstractMessage::OK);

                if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {
                    switch ($document->getState()) {
                        case DocumentWorkflow::STATE_POSTPONED_NONE:
                        case DocumentWorkflow::STATE_DISCARDED_NONE:
                        case DocumentWorkflow::STATE_NONE_INACTIVE:
                        case DocumentWorkflow::STATE_NONE_ACTIVE:
                        case DocumentWorkflow::STATE_NONE_DELETED:

                            if (
                                $this->bookmarkRepository->removeBookmark(
                                    $document,
                                    $this->security->getUser()->getUid()
                                )
                            ) {
                                $this->addFlashMessage(
                                    LocalizationUtility::translate("manager.workspace.bookmarkRemoved.singular", "dpf"),
                                    '',
                                    AbstractMessage::INFO
                                );
                            }

                            break;
                    }
                }

                $this->redirectToDocumentList();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:' . $messageKeyPart . '.failure';
                $this->flashMessage($document, $key, AbstractMessage::ERROR);
                $this->redirect('showDetails', 'Document', NULL, ['document' => $document]);
            }
        } catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $e) {
            // A redirect always throws this exception, but in this case, however,
            // redirection is desired and should not lead to an exception handling
        } catch (\Exception $exception) {
            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:' . $messageKeyPart . '.failure';
            }
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirectToDocumentList();
        }
    }
}
