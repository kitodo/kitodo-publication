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

/**
 * DocumentController
 */
class DocumentManagerController extends \EWW\Dpf\Controller\AbstractController
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
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        $documents = $this->documentRepository->findAll();

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
        $documents = $this->documentRepository->getNewDocuments();
        $this->view->assign('documents', $documents);
    }

    public function listEditAction()
    {
        $documents = $this->documentRepository->getInProgressDocuments();
        $this->view->assign('documents', $documents);
    }

    /**
     * action show
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function showAction(\EWW\Dpf\Domain\Model\Document $document)
    {

        $this->view->assign('document', $document);
    }

    /**
     * action new
     *
     * @param \EWW\Dpf\Domain\Model\Document $newDocument
     * @ignorevalidation $newDocument
     * @return void
     */
    public function newAction(\EWW\Dpf\Domain\Model\Document $newDocument = null)
    {

        $this->view->assign('newDocument', $newDocument);
    }

    /**
     * action create
     *
     * @param \EWW\Dpf\Domain\Model\Document $newDocument
     * @return void
     */
    public function createAction(\EWW\Dpf\Domain\Model\Document $newDocument)
    {

        $this->addFlashMessage('The object was created. Please be aware that this action is publicly accessible unless you implement an access check. See <a href="http://wiki.typo3.org/T3Doc/Extension_Builder/Using_the_Extension_Builder#1._Model_the_domain" target="_blank">Wiki</a>', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
        $this->documentRepository->add($newDocument);
        $this->redirect('list');
    }

    /**
     * action edit
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @ignorevalidation $document
     * @return void
     */
    public function editAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $this->view->assign('document', $document);
    }

    /**
     * action update
     *
     * @param \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function updateAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $this->addFlashMessage('The object was updated. Please be aware that this action is publicly accessible unless you implement an access check. See <a href="http://wiki.typo3.org/T3Doc/Extension_Builder/Using_the_Extension_Builder#1._Model_the_domain" target="_blank">Wiki</a>', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
        $this->documentRepository->update($document);
        $this->redirect('list');
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
        // remove document from local index
        $elasticsearchRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\ElasticsearchRepository');
        // send document to index
        $elasticsearchRepository->delete($document, "");

        $this->documentRepository->remove($document);

        $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_discard.success';

        $args = array();

        $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf', $args);
        $message = empty($message) ? "" : $message;

        $this->addFlashMessage($message, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);

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

        $args = array();

        $key     = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_duplicate.success';
        $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf', $args);
        $message = empty($message) ? "" : $message;

        $this->addFlashMessage($message, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);

        $newDocument = $this->objectManager->get('\EWW\Dpf\Domain\Model\Document');

        $newDocument->setTitle($document->getTitle());
        $newDocument->setAuthors($document->getAuthors());

        $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());
        $mods->clearAllUrn();
        $newDocument->setXmlData($mods->getModsXml());

        $newDocument->setDocumentType($document->getDocumentType());

        $processNumberGenerator = $this->objectManager->get("EWW\\Dpf\\Services\\ProcessNumber\\ProcessNumberGenerator");
        $processNumber = $processNumberGenerator->getProcessNumber();
        $newDocument->setProcessNumber($processNumber);

        $slub = new \EWW\Dpf\Helper\Slub($document->getSlubInfoData());
        $slub->setProcessNumber($processNumber);
        $newDocument->setSlubInfoData($slub->getSlubXml());

        $this->documentRepository->add($newDocument);

        $elasticsearchRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\ElasticsearchRepository');

        $this->persistenceManager->persistAll();
        // send document to index
        $elasticsearchMapper = $this->objectManager->get('EWW\Dpf\Helper\ElasticsearchMapper');
        $json                = $elasticsearchMapper->getElasticsearchJson($newDocument);

        $elasticsearchRepository->add($newDocument, $json);

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

        // generate URN if needed
        $qucosaId = $document->getObjectIdentifier();
        if (empty($qucosaId)) {
            $qucosaId = $document->getReservedObjectIdentifier();
        }
        if (empty($qucosaId)) {
            $documentTransferManager = $this->objectManager->get('\EWW\Dpf\Services\Transfer\DocumentTransferManager');
            $remoteRepository        = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');
            $documentTransferManager->setRemoteRepository($remoteRepository);
            $qucosaId = $documentTransferManager->getNextDocumentId();
            $document->setReservedObjectIdentifier($qucosaId);
        }

        $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());
        if (!$mods->hasQucosaUrn()) {
            $urnService = $this->objectManager->get('EWW\\Dpf\\Services\\Identifier\\Urn');
            $urn        = $urnService->getUrn($qucosaId);
            $mods->addQucosaUrn($urn);
            $document->setXmlData($mods->getModsXml());
        }

        $documentTransferManager = $this->objectManager->get('\EWW\Dpf\Services\Transfer\DocumentTransferManager');
        $remoteRepository        = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');
        $documentTransferManager->setRemoteRepository($remoteRepository);

        $objectIdentifier = $document->getObjectIdentifier();

        if (empty($objectIdentifier)) {

            // Document is not in the fedora repository.

            if ($documentTransferManager->ingest($document)) {
                $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_ingest.success';
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
                $notifier = $this->objectManager->get('\EWW\Dpf\Services\Email\Notifier');
                $notifier->sendIngestNotification($document);
            } else {
                $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_ingest.failure';
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;
            }

        } else {

            // Document needs to be updated.

            if ($documentTransferManager->update($document)) {
                $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.success';
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
            } else {
                $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.failure';
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;
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

        $documentTransferManager = $this->objectManager->get('\EWW\Dpf\Services\Transfer\DocumentTransferManager');
        $remoteRepository        = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');
        $documentTransferManager->setRemoteRepository($remoteRepository);

        if ($documentTransferManager->delete($document, "inactivate")) {
            $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_restore.success';
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
        } else {
            $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_restore.failure';
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;
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

        $documentTransferManager = $this->objectManager->get('\EWW\Dpf\Services\Transfer\DocumentTransferManager');
        $remoteRepository        = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');
        $documentTransferManager->setRemoteRepository($remoteRepository);

        if ($documentTransferManager->delete($document, "")) {
            $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_delete.success';
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
        } else {
            $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_delete.failure';
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;
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

        $documentTransferManager = $this->objectManager->get('\EWW\Dpf\Services\Transfer\DocumentTransferManager');
        $remoteRepository        = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');
        $documentTransferManager->setRemoteRepository($remoteRepository);

        if ($documentTransferManager->delete($document, "revert")) {
            $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_activate.success';
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
        } else {
            $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_activate.failure';
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;
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

        $documentTransferManager = $this->objectManager->get('\EWW\Dpf\Services\Transfer\DocumentTransferManager');
        $remoteRepository        = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');
        $documentTransferManager->setRemoteRepository($remoteRepository);

        if ($documentTransferManager->delete($document, "inactivate")) {
            $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_inactivate.success';
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
        } else {
            $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_inactivate.failure';
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;
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
     */
    protected function flashMessage(\EWW\Dpf\Domain\Model\Document $document, $key, $severity)
    {

        // Show success or failure of the action in a flash message
        $args[] = $document->getTitle();
        $args[] = $document->getObjectIdentifier();

        $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf', $args);
        $message = empty($message) ? "" : $message;

        $this->addFlashMessage(
            $message,
            '',
            $severity,
            true
        );

    }
}
