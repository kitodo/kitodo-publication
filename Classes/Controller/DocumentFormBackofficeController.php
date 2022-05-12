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
use EWW\Dpf\Helper\DocumentMapper;
use EWW\Dpf\Exceptions\AccessDeniedExcepion;
use EWW\Dpf\Security\DocumentVoter;
use EWW\Dpf\Security\Security;
use EWW\Dpf\Exceptions\DPFExceptionInterface;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Services\Email\Notifier;
use EWW\Dpf\Domain\Model\DepositLicenseLog;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class DocumentFormBackofficeController extends AbstractDocumentFormController
{
    /**
     * editingLockService
     *
     * @var \EWW\Dpf\Services\Document\EditingLockService
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $editingLockService = null;

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
     * clientConfigurationManager
     *
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $clientConfigurationManager;

    /**
     * documentValidator
     *
     * @var \EWW\Dpf\Helper\DocumentValidator
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentValidator;

    public function arrayRecursiveDiff($aArray1, $aArray2) {
        $aReturn = array();

        foreach ($aArray1 as $mKey => $mValue) {
            if (array_key_exists($mKey, $aArray2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = $this->arrayRecursiveDiff($mValue, $aArray2[$mKey]);
                    if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; }
                } else {
                    if ($mValue != $aArray2[$mKey]) {
                        $aReturn[$mKey] = $mValue;
                    }
                }
            } else {
                $aReturn[$mKey] = $mValue;
            }
        }
        return $aReturn;
    }


    /**
     * action edit
     *
     * @param \EWW\Dpf\Domain\Model\DocumentForm $documentForm
     * @param bool $suggestMod
     * @param string activeGroup
     * @param int activeGroupIndex
     * @param bool $addCurrentFeUser
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("documentForm")
     * @return void
     */
    public function editAction(
        \EWW\Dpf\Domain\Model\DocumentForm $documentForm,
        bool $suggestMod = false,
        $activeGroup = '',
        $activeGroupIndex = 0,
        $addCurrentFeUser = true
    )
    {
        /** @var \EWW\Dpf\Domain\Model\Document $document */
        $document = $this->documentRepository->findByUid($documentForm->getDocumentUid());

        if ($suggestMod) {
            $documentVoterAttribute = DocumentVoter::SUGGEST_MODIFICATION;
        } else {
            $documentVoterAttribute = DocumentVoter::EDIT;
        }

        if (!$this->authorizationChecker->isGranted($documentVoterAttribute, $document)) {

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

            $this->addFlashMessage($message, '', AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        $this->view->assign('document', $document);
        $this->view->assign('suggestMod', $suggestMod);

        $this->editingLockService->lock(
            ($document->getObjectIdentifier()? $document->getObjectIdentifier() : $document->getUid()),
            $this->security->getUser()->getUid()
        );

        $this->view->assign('activeGroup', $activeGroup);
        $this->view->assign('activeGroupIndex', $activeGroupIndex);
        $this->view->assign('addCurrentFeUser', $addCurrentFeUser);
        parent::editAction($documentForm);
    }

    /**
     * @param \EWW\Dpf\Domain\Model\DocumentForm $documentForm
     * @param bool $restore
     */
    public function createSuggestionDocumentAction(\EWW\Dpf\Domain\Model\DocumentForm $documentForm, $restore = FALSE)
    {
        /** @var DocumentMapper $documentMapper */
        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        /* @var $document \EWW\Dpf\Domain\Model\Document */
        $document = $this->documentRepository->findByUid($documentForm->getDocumentUid());

        if ($document->isTemporary()) {
            $document->setTemporary(false);
        }

        /** @var Document $newDocument */
        $newDocument = $this->objectManager->get(Document::class);
        $newDocument->setSuggestion(true);
        $this->documentRepository->add($newDocument);
        $this->persistenceManager->persistAll();

        /* @var $newDocument \EWW\Dpf\Domain\Model\Document */
        $newDocument = $newDocument->copy($document);
        $documentForm->setDocumentUid($newDocument->getUid());
        $newDocument = $documentMapper->getDocument($documentForm);

        if ($document->getObjectIdentifier()) {
            $newDocument->setLinkedUid($document->getObjectIdentifier());
        } else {
            $newDocument->setLinkedUid($document->getUid());
        }

        if (!$this->documentValidator->validate($newDocument, false)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_suggestChange.missingValues';
            $newDocument->setLinkedUid('');
            $this->documentRepository->update($newDocument);
            $this->documentRepository->remove($newDocument);
            $this->persistenceManager->persistAll();
            $this->flashMessage($newDocument, $key, AbstractMessage::ERROR);
            $this->redirect('showDetails','Document',NULL, ['document' => $document]);
        }

        if ($restore) {
            $newDocument->setTransferStatus("RESTORE");
        }

        try {
            $newDocument->setCreator($this->security->getUser()->getUid());
            $this->documentRepository->add($newDocument);

            $flashMessage = $this->clientConfigurationManager->getSuggestionFlashMessage();
            if (!$flashMessage) {
                $flashMessage = LocalizationUtility::translate(
                    'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:message.suggestion_flashmessage',
                    'dpf',
                    ''
                );
            }
            $this->addFlashMessage($flashMessage, '', AbstractMessage::OK, true);

            $notifier = $this->objectManager->get(Notifier::class);
            $notifier->sendAdminNewSuggestionNotification($newDocument);

            $depositLicenseLog = $this->depositLicenseLogRepository->findOneByProcessNumber($newDocument->getProcessNumber());
            if (empty($depositLicenseLog) && $newDocument->getDepositLicense()) {
                // Only if there was no deposit license a notification may be sent

                /** @var DepositLicenseLog $depositLicenseLog */
                $depositLicenseLog = $this->objectManager->get(DepositLicenseLog::class);
                $depositLicenseLog->setUsername($this->security->getUsername());
                $depositLicenseLog->setObjectIdentifier($newDocument->getObjectIdentifier());
                $depositLicenseLog->setProcessNumber($newDocument->getProcessNumber());
                $depositLicenseLog->setTitle($newDocument->getTitle());
                $depositLicenseLog->setUrn($newDocument->getPrimaryUrn());
                $depositLicenseLog->setLicenceUri($newDocument->getDepositLicense());

                if ($newDocument->hasFiles()) {
                    $fileList = [];
                    foreach ($newDocument->getFile() as $file) {
                        $fileList[] = $file->getTitle();
                    }
                    $depositLicenseLog->setFileNames(implode(", ", $fileList));
                }


                $this->depositLicenseLogRepository->add($depositLicenseLog);

                /** @var Notifier $notifier */
                $notifier = $this->objectManager->get(Notifier::class);
                $notifier->sendDepositLicenseNotification($newDocument);
            }

        } catch (\Throwable $t) {
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;
            $this->addFlashMessage("Failed", '', $severity,false);
        }

        $this->redirectToDocumentList();
    }


    public function updateAction(\EWW\Dpf\Domain\Model\DocumentForm $documentForm)
    {
        if ($this->request->getArgument('documentData')['suggestMod']) {
            $restore = $this->request->getArgument('documentData')['suggestRestore'];
            $this->forward('createSuggestionDocument', null, null, ['documentForm' => $documentForm, 'restore' => $restore]);
        }

        $backToList = $this->request->getArgument('documentData')['backToList'];

        if ($this->request->hasArgument('saveAndUpdate')) {
            $saveMode = 'saveAndUpdate';
        } elseif ($this->request->hasArgument('saveWorkingCopy')) {
            $saveMode = 'saveWorkingCopy';
        } else {
            $saveMode = null;
        }

        $this->forward(

            'updateDocument',
            NULL,
            NULL,
                [
                    'documentForm' => $documentForm,
                    'saveMode' => $saveMode,
                    'backToList' => $backToList
                ]
        );
    }


    /**
     * @param \EWW\Dpf\Domain\Model\DocumentForm $documentForm
     * @param string $saveMode
     * @param bool $backToList
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function updateDocumentAction(\EWW\Dpf\Domain\Model\DocumentForm $documentForm, $saveMode = null, $backToList = false)
    {
        try {
            /** @var \EWW\Dpf\Domain\Model\Document $document */
            $document = $this->documentRepository->findByUid($documentForm->getDocumentUid());
            $depositLicense = $document->getDepositLicense();

            if (
                !$this->authorizationChecker->isGranted(DocumentVoter::UPDATE, $document) ||
                (
                    $saveMode == 'saveWorkingCopy' &&
                    $this->security->getUserRole() !== Security::ROLE_LIBRARIAN
                )
            ) {
                $message = LocalizationUtility::translate(
                    'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.accessDenied',
                    'dpf',
                    array($document->getTitle())
                );
                $this->addFlashMessage($message, '', AbstractMessage::ERROR);

                $this->redirect('cancelEdit',
                    null,
                    null,
                    ['documentUid' => $document->getUid(), 'backToList' => $backToList]
                );
            }

            /** @var  \EWW\Dpf\Helper\DocumentMapper $documentMapper */
            $documentMapper = $this->objectManager->get(DocumentMapper::class);

            /** @var \EWW\Dpf\Domain\Model\Document $updateDocument */
            $updateDocument = $documentMapper->getDocument($documentForm);

            $saveWorkingCopy = false;
            $workflowTransition = null;

            // Convert the temporary copy into a local working copy if needed.
            if ( $updateDocument->isTemporaryCopy() && $saveMode == 'saveWorkingCopy') {
                $saveWorkingCopy = true;
                $updateDocument->setTemporary(false);
                $workflowTransition = DocumentWorkflow::TRANSITION_IN_PROGRESS;
            } elseif ($updateDocument->isTemporaryCopy() && $saveMode == 'saveAndUpdate') {
                $workflowTransition = DocumentWorkflow::TRANSITION_REMOTE_UPDATE;
            } elseif (
                $this->security->getUserRole() === Security::ROLE_LIBRARIAN &&
                $updateDocument->getState() === DocumentWorkflow::STATE_REGISTERED_NONE
            ) {
                $workflowTransition = DocumentWorkflow::TRANSITION_IN_PROGRESS;
            }

            if (
                $workflowTransition === DocumentWorkflow::TRANSITION_REMOTE_UPDATE
                && $document->getRemoteState() === DocumentWorkflow::REMOTE_STATE_ACTIVE
            ) {
                if (!$this->documentValidator->validate($document)) {
                    $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_release.missingValues';
                    $message = LocalizationUtility::translate(
                        $key,
                        'dpf',
                        array($document->getTitle())
                    );
                    $this->addFlashMessage($message, '', AbstractMessage::ERROR);
                    $this->redirect('showDetails', 'Document', null, ['document' => $document]);
                }
            }

            if ($this->documentManager->update($updateDocument, $workflowTransition)) {

                $depositLicenseLog = $this->depositLicenseLogRepository->findOneByProcessNumber($document->getProcessNumber());
                if (
                    (empty($depositLicenseLog) || $document->getState() == "NEW:NONE")
                    && $updateDocument->getDepositLicense()
                ) {
                    // Only if there was no deposit license a notification may be sent

                    if (empty($depositLicenseLog)) {
                        /** @var DepositLicenseLog $depositLicenseLog */
                        $depositLicenseLog = $this->objectManager->get(DepositLicenseLog::class);
                    }

                    $depositLicenseLog->setUsername($this->security->getUsername());
                    $depositLicenseLog->setObjectIdentifier($document->getObjectIdentifier());
                    $depositLicenseLog->setProcessNumber($document->getProcessNumber());
                    $depositLicenseLog->setTitle($document->getTitle());
                    $depositLicenseLog->setUrn($document->getPrimaryUrn());
                    $depositLicenseLog->setLicenceUri($document->getDepositLicense());

                    if ($document->hasFiles()) {
                        $fileList = [];
                        foreach ($document->getFile() as $file) {
                            $fileList[] = $file->getTitle();
                        }
                        $depositLicenseLog->setFileNames(implode(", ", $fileList));
                    }

                    if ($depositLicenseLog->getUid()) {
                        $this->depositLicenseLogRepository->update($depositLicenseLog);
                    } else {
                        $this->depositLicenseLogRepository->add($depositLicenseLog);
                    }

                    /** @var Notifier $notifier */
                    $notifier = $this->objectManager->get(Notifier::class);
                    $notifier->sendDepositLicenseNotification($updateDocument);
                }

                $message = LocalizationUtility::translate(
                    'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.success',
                    'dpf',
                    array($updateDocument->getTitle())
                );
                $this->addFlashMessage($message, '', AbstractMessage::OK);

                if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN) {
                    if ($saveWorkingCopy) {
                        if (
                            $this->bookmarkRepository->addBookmark(
                                $updateDocument,
                                $this->security->getUser()->getUid()
                            )
                        ) {
                            $this->addFlashMessage(
                                LocalizationUtility::translate(
                                    "manager.workspace.bookmarkAdded", "dpf"
                                ),
                                '',
                                AbstractMessage::INFO
                            );
                        }
                    } else {
                        switch ($document->getState()) {
                            case DocumentWorkflow::STATE_POSTPONED_NONE:
                            case DocumentWorkflow::STATE_DISCARDED_NONE:
                            case DocumentWorkflow::STATE_NONE_INACTIVE:
                            case DocumentWorkflow::STATE_NONE_ACTIVE:
                            case DocumentWorkflow::STATE_NONE_DELETED:

                                if (
                                    $this->bookmarkRepository->removeBookmark(
                                        $updateDocument,
                                        $this->security->getUser()->getUid()
                                    )
                                ) {
                                    $this->addFlashMessage(
                                        LocalizationUtility::translate(
                                            "manager.workspace.bookmarkRemoved.singular", "dpf"
                                        ),
                                        '',
                                        AbstractMessage::INFO
                                    );
                                }

                                $this->redirectToDocumentList();

                                break;
                        }
                    }
                }

            } else {
                $message = LocalizationUtility::translate(
                    'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.failure',
                    'dpf',
                    array($updateDocument->getTitle())
                );
                $this->addFlashMessage($message, '', AbstractMessage::ERROR);
            }

            if ($workflowTransition && $workflowTransition === DocumentWorkflow::TRANSITION_REMOTE_UPDATE) {
                $this->redirectToDocumentList();
            } else {
                $this->redirect('cancelEdit',
                    null,
                    null,
                    ['documentUid' => $updateDocument->getUid(), 'backToList' => $backToList]
                );
                // $this->redirect('showDetails', 'Document', null, ['document' => $updateDocument]);
            }
        } catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $e) {
            // A redirect always throws this exception, but in this case, however,
            // redirection is desired and should not lead to an exception handling
        } catch (\Exception $exception) {

            $severity = AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }

            $exceptionMsg[] = LocalizationUtility::translate(
                $key,
                'dpf',
                array((isset($updateDocument) ? $updateDocument->getTitle() : ''))
            );

            $this->addFlashMessage(implode(" ", $exceptionMsg), '', $severity, true);
            $this->redirect('cancelEdit',
                null,
                null,
                ['documentUid' => $updateDocument->getUid(), 'backToList' => $backToList]
            );
            $this->redirect('showDetails', 'Document', null, ['document' => $updateDocument]);
        }
    }

    public function createAction(\EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::CREATE, new Document())) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:documentForm.create.accessDenied';
            $message = LocalizationUtility::translate($key, 'dpf', []);
            $this->addFlashMessage($message, '', AbstractMessage::ERROR);
            $this->redirect('list', 'Document');
            return FALSE;
        }

        try {
            parent::createAction($newDocumentForm);

            $severity = AbstractMessage::OK;
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:documentForm.create.ok';
            $message = LocalizationUtility::translate($key, 'dpf');
            $this->addFlashMessage(
                $message,
                '',
                $severity,
                true
            );

        } catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $e) {
            // A redirect always throws this exception, but in this case, however,
            // redirection is desired and should not lead to an exception handling
        } catch (\Exception $exception) {

            $severity = AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }

            $message[] = LocalizationUtility::translate($key, 'dpf');

            $this->addFlashMessage(
                implode(" ", $message),
                '',
                $severity,
                true
            );
        }

        $this->redirect('listWorkspace', 'Workspace');
    }


    /**
     * action cancel edit
     *
     * @param integer $documentUid
     * @param bool $backToList
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     *
     * @return void
     */
    public function cancelEditAction($documentUid = 0, $backToList = false)
    {
        if (empty($documentUid) || $backToList) {
            $this->redirectToDocumentList();
        }

        /** @var $document \EWW\Dpf\Domain\Model\Document */
        $document = $this->documentRepository->findByUid($documentUid);
        $this->redirect('showDetails', 'Document', null, ['document' => $document]);
    }

    /**
     * action cancel new
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     *
     * @return void
     */
    public function cancelNewAction()
    {
        $this->redirect('list');
    }


    public function initializeAction()
    {
        $this->authorizationChecker->denyAccessUnlessLoggedIn();

        parent::initializeAction();

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
            $this->redirect($action, $controller, null, array('message' => $message));
        }
    }
}
