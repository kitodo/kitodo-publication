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

use EWW\Dpf\Helper\DocumentMapper;
use EWW\Dpf\Exceptions\AccessDeniedExcepion;
use EWW\Dpf\Security\DocumentVoter;
use EWW\Dpf\Security\Security;
use EWW\Dpf\Exceptions\DPFExceptionInterface;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Services\Transfer\DocumentTransferManager;
use EWW\Dpf\Services\Transfer\FedoraRepository;
use TYPO3\CMS\Core\Messaging\AbstractMessage;


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


    /**
     * action edit
     *
     * @param \EWW\Dpf\Domain\Model\DocumentForm $documentForm
     * @ignorevalidation $documentForm
     * @return void
     */
    public function editAction(\EWW\Dpf\Domain\Model\DocumentForm $documentForm)
    {
        /** @var \EWW\Dpf\Domain\Model\Document $document */
        $document = $this->documentRepository->findByUid($documentForm->getDocumentUid());

        if (!$this->authorizationChecker->isGranted(DocumentVoter::EDIT, $document)) {
            $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_edit.failureBlocked',
                'dpf',
                array($document->getTitle())
            );
            $this->addFlashMessage($message, '', AbstractMessage::ERROR);
            $this->redirect('showDetails', 'Document', null, ['document' => $document]);
            return FALSE;
        }

        $document->setEditorUid($this->security->getUser()->getUid());
        $this->documentRepository->update($document);
        $this->persistenceManager->persistAll();
        parent::editAction($documentForm);
    }

    public function updateAction(\EWW\Dpf\Domain\Model\DocumentForm $documentForm)
    {
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
                    $updateDocument->setTemporary(FALSE);
                }
            }

            if ($this->security->getUserRole() === Security::ROLE_LIBRARIAN &&
                $updateDocument->getState() === DocumentWorkflow::STATE_REGISTERED_NONE) {

                $state = explode(":", $updateDocument->getState());

                $state[0] = DocumentWorkflow::LOCAL_STATE_IN_PROGRESS;
                $updateDocument->setState(implode(":", $state));
                die("state");
            }

            if (!$updateDocument->getTemporary()) {
                $updateDocument->setEditorUid(0);
            }

            $this->documentRepository->update($updateDocument);

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
            parent::updateAction($documentForm);

            $documentMapper = $this->objectManager->get(\EWW\Dpf\Helper\DocumentMapper::class);

            /** @var \EWW\Dpf\Domain\Model\Document $updateDocument */
            $updateDocument = $documentMapper->getDocument($documentForm);

            $this->documentTransferManager->update($updateDocument);

            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.success';
            $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf', [$document->getTitle()]);
            $this->addFlashMessage( $message, '', AbstractMessage::OK);
            $this->redirectToDocumentList();

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

        $this->redirectToList();
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
