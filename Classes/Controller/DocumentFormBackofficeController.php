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
use EWW\Dpf\Services\Transfer\DocumentTransferManager;
use EWW\Dpf\Services\Transfer\FedoraRepository;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class DocumentFormBackofficeController extends AbstractDocumentFormController
{
    /**
     * documentTransferManager
     *
     * @var \EWW\Dpf\Services\Transfer\DocumentTransferManager $documentTransferManager
     */
    protected $documentTransferManager;

    /**
     * fedoraRepository
     *
     * @var \EWW\Dpf\Services\Transfer\FedoraRepository $fedoraRepository
     */
    protected $fedoraRepository;

    /**
     * editingLockService
     *
     * @var \EWW\Dpf\Services\Document\EditingLockService
     * @inject
     */
    protected $editingLockService = null;

    /**
     * documentManager
     *
     * @var \EWW\Dpf\Services\Document\DocumentManager
     * @inject
     */
    protected $documentManager = null;

    /**
     * bookmarkRepository
     *
     * @var \EWW\Dpf\Domain\Repository\BookmarkRepository
     * @inject
     */
    protected $bookmarkRepository = null;

    /**
     * DocumentController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->documentTransferManager = $objectManager->get(DocumentTransferManager::class);
        $this->fedoraRepository = $objectManager->get(FedoraRepository::class);
        $this->documentTransferManager->setRemoteRepository($this->fedoraRepository);
    }

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
     * @param bool activeFileTab
     * @ignorevalidation $documentForm
     * @return void
     */
    public function editAction(
        \EWW\Dpf\Domain\Model\DocumentForm $documentForm, bool $suggestMod = false, $activeFileTab = false
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

        $this->view->assign('activeFileTab', $activeFileTab);
        parent::editAction($documentForm);
    }

    /**
     * @param \EWW\Dpf\Domain\Model\DocumentForm $documentForm
     * @param bool $restore
     */
    public function createSuggestionDocumentAction(\EWW\Dpf\Domain\Model\DocumentForm $documentForm, $restore = FALSE)
    {
        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        $hasFilesFlag = true;

        $workingCopy = $this->documentRepository->findByUid($documentForm->getDocumentUid());

        if ($workingCopy->isTemporary()) {
            $workingCopy->setTemporary(false);
        }

        if (empty($workingCopy->getFileData())) {
            // no files are linked to the document
            $hasFilesFlag = false;
        }

        $newDocument = $this->objectManager->get(Document::class);

        $this->documentRepository->add($newDocument);
        $this->persistenceManager->persistAll();

        /* @var $document \EWW\Dpf\Domain\Model\Document */
        $document = $documentMapper->getDocument($documentForm);

        /* @var $newDocument \EWW\Dpf\Domain\Model\Document */
        $newDocument = $newDocument->copy($document);

        if ($document->getObjectIdentifier()) {
            $newDocument->setLinkedUid($document->getObjectIdentifier());
        } else {
            $newDocument->setLinkedUid($document->getUid());
        }

        $newDocument->setSuggestion(true);
        $newDocument->setComment($document->getComment());

        if ($restore) {
            $newDocument->setTransferStatus("RESTORE");
        }

        if (!$hasFilesFlag) {
            // Add or update files
            foreach ($documentForm->getNewFiles() as $newFile) {
                if ($newFile->getUID()) {
                    $this->fileRepository->update($newFile);
                } else {
                    $newFile->setDocument($newDocument);
                    $this->fileRepository->add($newFile);
                }

            }
        } else {
            // remove files for suggest object
            $newDocument->setFile($this->objectManager->get(ObjectStorage::class));
        }


        try {
            $newDocument->setCreator($this->security->getUser()->getUid());
            $this->documentRepository->add($newDocument);
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::SUCCESS;
            $this->addFlashMessage("Success", '', $severity,false);
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
                    'saveMode' => $saveMode
                ]
        );
    }


    /**
     * @param \EWW\Dpf\Domain\Model\DocumentForm $documentForm
     * @param string $saveMode
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function updateDocumentAction(\EWW\Dpf\Domain\Model\DocumentForm $documentForm, $saveMode = null)
    {
        try {
            /** @var \EWW\Dpf\Domain\Model\Document $document */
            $document = $this->documentRepository->findByUid($documentForm->getDocumentUid());

            if (
                !$this->authorizationChecker->isGranted(DocumentVoter::UPDATE, $document) ||
                (
                    $saveMode == 'saveWorkingCopy' &&
                    $this->security->getUser()->getUserRole() !== Security::ROLE_LIBRARIAN
                )
            ) {
                $message = LocalizationUtility::translate(
                    'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.accessDenied',
                    'dpf',
                    array($document->getTitle())
                );
                $this->addFlashMessage($message, '', AbstractMessage::ERROR);
                $this->redirect(
                    'showDetails', 'Document',
                    null, ['document' => $document]
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
                $this->security->getUser()->getUserRole() === Security::ROLE_LIBRARIAN &&
                $updateDocument->getState() === DocumentWorkflow::STATE_REGISTERED_NONE
            ) {
                $workflowTransition = DocumentWorkflow::TRANSITION_IN_PROGRESS;
            }

            if (
                $this->documentManager->update(
                    $updateDocument, $workflowTransition,
                    $documentForm->getDeletedFiles(), $documentForm->getNewFiles()
                )
            ) {

                $message = LocalizationUtility::translate(
                    'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.success',
                    'dpf',
                    array($updateDocument->getTitle())
                );
                $this->addFlashMessage($message, '', AbstractMessage::OK);

                if ($this->security->getUser()->getUserRole() === Security::ROLE_LIBRARIAN) {
                    if ($saveWorkingCopy) {
                        if (
                            $this->bookmarkRepository->addBookmark(
                                $this->security->getUser()->getUid(), $updateDocument
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
                $this->redirect('showDetails', 'Document', null, ['document' => $updateDocument]);
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
                'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.failure',
                'dpf',
                array($updateDocument->getTitle())
            );

            $exceptionMsg[] = LocalizationUtility::translate($key, 'dpf');

            $this->addFlashMessage(implode(" ", $exceptionMsg), '', $severity, true);
            $this->redirect('showDetails', 'Document', null, ['document' => $updateDocument]);
        }
    }

    public function createAction(\EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm)
    {
        /** @var \EWW\Dpf\Helper\DocumentMapper $documentMapper */
        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        /** @var \EWW\Dpf\Domain\Model\Document $document */
        $document = $documentMapper->getDocument($newDocumentForm);

        if (!$this->authorizationChecker->isGranted(DocumentVoter::CREATE, $document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:documentForm.create.accessDenied';
            $args[] = $document->getTitle();
            $message = LocalizationUtility::translate($key, 'dpf', $args);
            $this->addFlashMessage($message, '', AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
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
     * @param bool $documentList
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     *
     * @return void
     */
    public function cancelEditAction($documentUid = 0, $documentList = false)
    {
        if ($documentList) {
            $this->redirectToDocumentList();
        }

        if ($documentUid) {
            /** @var $document \EWW\Dpf\Domain\Model\Document */
            $document = $this->documentRepository->findByUid($documentUid);

            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
        }
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
        list($action, $controller, $redirectUri) = $this->session->getListAction();

        if ($redirectUri) {
            $this->redirectToUri($redirectUri);
        } else {
            $this->redirect($action, $controller, null, array('message' => $message));
        }
    }


}
