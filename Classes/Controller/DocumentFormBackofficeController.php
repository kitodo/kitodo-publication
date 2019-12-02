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
     * @ignorevalidation $documentForm
     * @return void
     */
    public function editAction(\EWW\Dpf\Domain\Model\DocumentForm $documentForm, bool $suggestMod = false)
    {
        /** @var \EWW\Dpf\Domain\Model\Document $document */
        $document = $this->documentRepository->findByUid($documentForm->getDocumentUid());

        if ($suggestMod) {
            $documentVoterAttribute = DocumentVoter::SUGGEST_MODIFICATION;
        } else {
            $documentVoterAttribute = DocumentVoter::EDIT;
        }

        if (!$this->authorizationChecker->isGranted($documentVoterAttribute, $document)) {

            if ($document->getOwner() !== $this->security->getUser()->getUid()) {
                $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_edit.accessDenied',
                    'dpf',
                    array($document->getTitle())
                );
            } else {
                $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
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
        $document->setEditorUid($this->security->getUser()->getUid());
        $this->documentRepository->update($document);
        $this->persistenceManager->persistAll();
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

        if ($workingCopy->getTemporary()) {
            $workingCopy->setTemporary(false);
            $workingCopy->setEditorUid(0);
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
            $newDocument->setOwner($this->security->getUser()->getUid());
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
            $this->forward('updateRemote',NULL, NULL, ['documentForm' => $documentForm]);
        } else {
            $this->forward(
                'updateLocally',
                NULL,
                NULL,
                [
                    'documentForm' => $documentForm,
                    'workingCopy' => $this->request->hasArgument('saveWorkingCopy')
                ]
            );

        }

    }

    /**
     * @param \EWW\Dpf\Domain\Model\DocumentForm $documentForm
     * @param bool $workingCopy
     * @return bool
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     */
    public function updateLocallyAction(\EWW\Dpf\Domain\Model\DocumentForm $documentForm, $workingCopy)
    {
        /** @var \EWW\Dpf\Domain\Model\Document $document */
        $document = $this->documentRepository->findByUid($documentForm->getDocumentUid());

        if (!$this->authorizationChecker->isGranted(DocumentVoter::UPDATE, $document)) {
            $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_updateLocally.accessDenied',
                'dpf',
                array($document->getTitle())
            );
            $this->addFlashMessage($message, '', AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        /** @var \EWW\Dpf\Domain\Model\Document $updateDocument */
        $updateDocument = $documentMapper->getDocument($documentForm);

        try {
            parent::updateAction($documentForm);

            if ($updateDocument->getTemporary()) {
                if ($workingCopy) {
                    $documents = $this->documentRepository->findByObjectIdentifier($updateDocument->getObjectIdentifier());
                    foreach ($documents as $document) {
                        if (!$document->getTemporary()) {
                            $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                                'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_updateLocally.failureCreateWorkingCopy',
                                'dpf',
                                array($document->getTitle())
                            );
                            $this->addFlashMessage($message, '', AbstractMessage::ERROR);
                            $this->redirect('showDetails', 'Document', null, ['document' => $updateDocument]);
                        }
                    }
                    $updateDocument->setTemporary(false);
                }
            }

            if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN &&
                $updateDocument->getState() === DocumentWorkflow::STATE_REGISTERED_NONE) {

                $state = explode(":", $updateDocument->getState());

                $state[0] = DocumentWorkflow::LOCAL_STATE_IN_PROGRESS;
                $updateDocument->setState(implode(":", $state));
            }

            if (!$updateDocument->getTemporary()) {
                $updateDocument->setEditorUid(0);
            }

            $this->documentRepository->update($updateDocument);

            $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_updateLocally.success',
                'dpf',
                array($updateDocument->getTitle())
            );
            $this->addFlashMessage($message, '', AbstractMessage::OK);
            $this->redirect('showDetails', 'Document', null, ['document' => $updateDocument]);

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

            $message[] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.failure',
                'dpf',
                array($updateDocument->getTitle())
            );

            $message[] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf');

            $this->addFlashMessage(implode(" ", $message), '', $severity,true);
            $this->redirect('showDetails', 'Document', null, ['document' => $updateDocument]);
        }
    }


    public function updateRemoteAction(\EWW\Dpf\Domain\Model\DocumentForm $documentForm)
    {
        /** @var \EWW\Dpf\Domain\Model\Document $document */
        $document = $this->documentRepository->findByUid($documentForm->getDocumentUid());

        if (!$this->authorizationChecker->isGranted(DocumentVoter::UPDATE, $document)) {
            $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.accessDenied',
                'dpf',
                array($document->getTitle())
            );
            $this->addFlashMessage($message, '', AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        try {
            $documentMapper = $this->objectManager->get(DocumentMapper::class);

            /** @var \EWW\Dpf\Domain\Model\Document $updateDocument */
            $updateDocument = $documentMapper->getDocument($documentForm);

            if ($this->documentTransferManager->getLastModDate($updateDocument->getObjectIdentifier()) === $updateDocument->getRemoteLastModDate()) {
                parent::updateAction($documentForm);
                $this->documentTransferManager->update($updateDocument);

                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.success';
                $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf', [$updateDocument->getTitle()]);
                $this->addFlashMessage($message, '', AbstractMessage::OK);
                $this->redirectToDocumentList();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.failureNewVersion';
                $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf', [$updateDocument->getTitle()]);
                $this->addFlashMessage($message, '', AbstractMessage::ERROR);
                $this->redirectToDocumentList();
            }
        } catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $e) {
            // A redirect always throws this exception, but in this case, however,
            // redirection is desired and should not lead to an exception handling
        } catch (\Exception $exception) {
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }

            $documentMapper = $this->objectManager->get(\EWW\Dpf\Helper\DocumentMapper::class);
            $updateDocument = $documentMapper->getDocument($documentForm);

            $message[] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.failure',
                'dpf',
                array($updateDocument->getTitle())
            );

            $message[] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf');

            $this->addFlashMessage(implode(" ", $message), '', $severity,true);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
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
            $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf', $args);
            $this->addFlashMessage($message, '', AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        try {
            parent::createAction($newDocumentForm);

            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:documentForm.create.ok';
            $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf');
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

            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }

            $message[] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf');

            $this->addFlashMessage(
                implode(" ", $message),
                '',
                $severity,
                true
            );
        }

        $this->redirect('list', 'Document');
    }


    /**
     * action cancel edit
     *
     * @param integer $documentUid
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     *
     * @return void
     */
    public function cancelEditAction($documentUid = 0)
    {
        if ($documentUid) {
            /* @var $document \EWW\Dpf\Domain\Model\Document */
            $document = $this->documentRepository->findByUid($documentUid);

            if ($document) {
                if (!$document->getTemporary() && $document->getEditorUid() === $this->security->getUser()->getUid()) {
                    $document->setEditorUid(0);
                }
                $this->documentRepository->update($document);
            }

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
        $redirectAction = $this->getSessionData('redirectToDocumentListAction');
        $redirectController = $this->getSessionData('redirectToDocumentListController');
        $this->redirect($redirectAction, $redirectController, null, array('message' => $message));
    }


}
