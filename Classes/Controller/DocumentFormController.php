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

use EWW\Dpf\Domain\Model\DepositLicenseLog;
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Model\DocumentForm;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Exceptions\DPFExceptionInterface;
use EWW\Dpf\Helper\DocumentMapper;
use EWW\Dpf\Security\DocumentVoter;
use EWW\Dpf\Services\Email\Notifier;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class DocumentFormController extends AbstractDocumentFormController
{
    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Helper\DocumentMapper
     * @TYPO3\CMS\Extbase\Annotation\Inject
     */
    protected $documentMapper= null;

    /**
     * action new
     *
     * @param \EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm
     * @param int $returnDocumentId
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("newDocumentForm")
     * @return void
     */
    public function newAction(\EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm = null, $returnDocumentId = 0)
    {
        $this->view->assign('documentForm', $newDocumentForm);
    }

    /**
     * action create
     *
     * @param \EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm
     * @return void
     */
    public function createAction(\EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm)
    {
        try {
            parent::createAction($newDocumentForm);
            $this->redirect(
                'summary',
                null,
                null,
                ['document' => $this->newDocument]
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

            $this->addFlashMessage(implode(" ", $message), '', $severity,true);
            $this->forward('new', 'DocumentForm', null, array('newDocumentForm' => $newDocumentForm));
        }
    }

    /**
     * action edit
     *
     * @param \EWW\Dpf\Domain\Model\DocumentForm $documentForm
     * @TYPO3\CMS\Extbase\Annotation\IgnoreValidation("documentForm")
     * @return void
     */
    public function editAction(DocumentForm $documentForm)
    {
        $document = $this->documentMapper->getDocument($documentForm);

        if (!$this->authorizationChecker->isGranted(DocumentVoter::EDIT_ANONYMOUSLY, $document)) {

            $message = LocalizationUtility::translate(
                'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_edit.accessDenied',
                'dpf',
                array($document->getTitle())
            );

            $this->addFlashMessage($message, '', AbstractMessage::ERROR);
            $this->redirectToList();
            return;
        }

        parent::editAction($documentForm);
    }

    /**
     * action update
     *
     * @param \EWW\Dpf\Domain\Model\DocumentForm $documentForm
     * @return void
     */
    public function updateAction(DocumentForm $documentForm)
    {
        $documentMapper = $this->objectManager->get(DocumentMapper::class);

        /* @var $updateDocument \EWW\Dpf\Domain\Model\Document */
        $updateDocument = $documentMapper->getDocument($documentForm);

        // xml data fields are limited to 64 KB
        if (strlen($updateDocument->getXmlData()) >= Document::XML_DATA_SIZE_LIMIT) {
            throw new \EWW\Dpf\Exceptions\DocumentMaxSizeErrorException("Maximum document size exceeded.");
        }

        $updateDocument->setChanged(true);
        $this->documentRepository->update($updateDocument);

        $this->redirect(
            'summary',
            null,
            null,
            ['document' => $updateDocument]
        );
    }

    /**
     * action register
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function registerAction(Document $document)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::REGISTER_ANONYMOUSLY, $document)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_register.accessDenied';
            $this->flashMessage($document, $key, AbstractMessage::ERROR);
            $this->redirect('summary', 'DocumentForm', null, ['document' => $document]);
            return false;
        }

        $workflow = $this->objectManager->get(DocumentWorkflow::class)->getWorkflow();
        $workflow->apply($document, DocumentWorkflow::TRANSITION_REGISTER);

        $document->setTemporary(false);
        $this->documentRepository->update($document);

        $depositLicenseLog = $this->depositLicenseLogRepository->findOneByProcessNumber($document->getProcessNumber());
        if (empty($depositLicenseLog) && $document->getDepositLicense()) {
            // Only if there was no deposit license a notification may be sent

            /** @var DepositLicenseLog $depositLicenseLog */
            $depositLicenseLog = $this->objectManager->get(DepositLicenseLog::class);
            $depositLicenseLog->setUsername($this->security->getUsername());
            $depositLicenseLog->setObjectIdentifier($document->getObjectIdentifier());
            $depositLicenseLog->setProcessNumber($document->getProcessNumber());
            $depositLicenseLog->setTitle($document->getTitle());
            $depositLicenseLog->setUrn($document->getPrimaryUrn());
            $depositLicenseLog->setLicenceUri($document->getDepositLicense());

            if ($document->hasFiles()) {
                $fileList = [];
                foreach ($document->getFile() as $file) {
                    if (!$file->isFileGroupDeleted()) {
                        $fileList[] = $file->getTitle();
                    }
                }
                $depositLicenseLog->setFileNames(implode(", ", $fileList));
            }

            $this->depositLicenseLogRepository->add($depositLicenseLog);

            /** @var Notifier $notifier */
            $notifier = $this->objectManager->get(Notifier::class);
            $notifier->sendDepositLicenseNotification($document);
        }

        // admin register notification
        $notifier = $this->objectManager->get(Notifier::class);
        $notifier->sendRegisterNotification($document);

        // document updated notification
        $recipients = $this->documentManager->getUpdateNotificationRecipients($document);
        $notifier->sendMyPublicationUpdateNotification($document, $recipients);

        $recipients = $this->documentManager->getNewPublicationNotificationRecipients($document);
        $notifier->sendMyPublicationNewNotification($document, $recipients);

        // index the document
        $this->signalSlotDispatcher->dispatch(\EWW\Dpf\Controller\AbstractController::class, 'indexDocument', [$document]);

        $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_register.success';
        $this->flashMessage($document, $key, AbstractMessage::OK);
        $this->redirectToList();
    }

    /**
     * @param Document $document
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function summaryAction(Document $document)
    {
        if (!$this->authorizationChecker->isGranted(DocumentVoter::EDIT_ANONYMOUSLY, $document)) {
            $message = LocalizationUtility::translate(
                'manager.workspace.accessDenied', 'dpf'
            );
            $this->addFlashMessage($message, '', AbstractMessage::ERROR);
            $this->redirectToList();
            return;
        }

        $mapper = $this->objectManager->get(DocumentMapper::class);
        $documentForm = $mapper->getDocumentForm($document, false);

        $this->view->assign('documentForm', $documentForm);
        $this->view->assign('document', $document);
    }

    /**
     * @param Document $document
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function deleteAction(Document $document)
    {
        if ($this->authorizationChecker->isGranted(DocumentVoter::DELETE_ANONYMOUSLY, $document)) {
           $this->documentRepository->remove($document);
        } else {
            $message = LocalizationUtility::translate(
                'manager.workspace.accessDenied', 'dpf'
            );
            $this->addFlashMessage($message, '', AbstractMessage::ERROR);
        }

        $this->redirectToList();
    }
}
