<?php
namespace EWW\Dpf\Controller;


/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2014
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * DocumentController
 */
class DocumentController extends \EWW\Dpf\Controller\AbstractController {

	/**
	 * documentRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\DocumentRepository
	 * @inject
	 */
	protected $documentRepository = NULL;


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
	public function listAction() {
		$documents = $this->documentRepository->findAll();
		$this->view->assign('documents', $documents);
	}


        public function listNewAction() {
		$documents = $this->documentRepository->getNewDocuments();
		$this->view->assign('documents', $documents);
	}


        public function listEditAction() {
		$documents = $this->documentRepository->getInProgressDocuments();
		$this->view->assign('documents', $documents);
	}

	/**
	 * action show
	 *
	 * @param \EWW\Dpf\Domain\Model\Document $document
	 * @return void
	 */
	public function showAction(\EWW\Dpf\Domain\Model\Document $document) {

            $this->view->assign('document', $document);
	}

	/**
	 * action new
	 *
	 * @param \EWW\Dpf\Domain\Model\Document $newDocument
	 * @ignorevalidation $newDocument
	 * @return void
	 */
	public function newAction(\EWW\Dpf\Domain\Model\Document $newDocument = NULL) {

		$this->view->assign('newDocument', $newDocument);
	}


	/**
	 * action create
	 *
	 * @param \EWW\Dpf\Domain\Model\Document $newDocument
	 * @return void
	 */
	public function createAction(\EWW\Dpf\Domain\Model\Document $newDocument) {

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
	public function editAction(\EWW\Dpf\Domain\Model\Document $document) {
		$this->view->assign('document', $document);
	}

	/**
	 * action update
	 *
	 * @param \EWW\Dpf\Domain\Model\Document $document
	 * @return void
	 */
	public function updateAction(\EWW\Dpf\Domain\Model\Document $document) {
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
	public function discardConfirmAction(\EWW\Dpf\Domain\Model\Document $document) {
            $this->view->assign('document',$document);
	}


        /**
	 * action discard
	 *
	 * @param \EWW\Dpf\Domain\Model\Document $document
	 * @return void
	 */
	public function discardAction(\EWW\Dpf\Domain\Model\Document $document) {
                // remove document from local index
                $elasticsearchRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\ElasticsearchRepository');
                // send document to index
                $elasticsearchRepository->delete($document,"");

                $this->documentRepository->remove($document);

                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_discard.success';

                $args = array();

                $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key,'dpf',$args);
                $message = empty($message)? "" : $message;

                $this->addFlashMessage($message, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);

                $this->redirect('list');
	}


        /**
	 * action duplicate
	 *
	 * @param \EWW\Dpf\Domain\Model\Document $document
	 * @return void
	 */
	public function duplicateAction(\EWW\Dpf\Domain\Model\Document $document) {

                $args = array();

                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_duplicate.success';
                $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key,'dpf',$args);
                $message = empty($message)? "" : $message;

		$this->addFlashMessage($message, '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);

                $newDocument = $this->objectManager->get('\EWW\Dpf\Domain\Model\Document');

                $newDocument->setTitle($document->getTitle());
                $newDocument->setAuthors($document->getAuthors());

                $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());
                $mods->clearAllUrn();
                $newDocument->setXmlData($mods->getModsXml());
                $newDocument->setSlubInfoData($document->getSlubInfoData());

                $newDocument->setDocumentType($document->getDocumentType());
                

                $this->documentRepository->add($newDocument);

                $elasticsearchRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\ElasticsearchRepository');

                $this->persistenceManager->persistAll();
                // send document to index
                $elasticsearchMapper = $this->objectManager->get('EWW\Dpf\Helper\ElasticsearchMapper');
                $json = $elasticsearchMapper->getElasticsearchJson($newDocument);

                $elasticsearchRepository->add($newDocument, $json);
                // $elasticsearchRepository->delete($updateDocument);

		$this->redirect('list');
	}


       /**
         * action releaseConfirm
         *
         * @param \EWW\Dpf\Domain\Model\Document $document
         * @param string $releaseType
         * @return void
         */
        public function releaseConfirmAction(\EWW\Dpf\Domain\Model\Document $document, $releaseType) {
            $this->view->assign('releaseType',$releaseType);
            $this->view->assign('document',$document);
        }



        /**
         * action release
         *
         * @param \EWW\Dpf\Domain\Model\Document $document
         * @return void
         */
        public function releaseAction(\EWW\Dpf\Domain\Model\Document $document) {

          // generate URN if needed
          $qucosaId = $document->getObjectIdentifier();
          if (empty($qucosaId)) {
            $qucosaId = $document->getReservedObjectIdentifier();
          }
          if (empty($qucosaId)) {
            $documentTransferManager = $this->objectManager->get('\EWW\Dpf\Services\Transfer\DocumentTransferManager');
            $remoteRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');
            $documentTransferManager->setRemoteRepository($remoteRepository);
            $qucosaId = $documentTransferManager->getNextDocumentId();
            $document->setReservedObjectIdentifier($qucosaId);
          }

          $mods = new \EWW\Dpf\Helper\Mods($document->getXmlData());
          if (!$mods->hasQucosaUrn() ) {
               $urnService = $this->objectManager->get('EWW\\Dpf\\Services\\Identifier\\Urn');
               $urn = $urnService->getUrn($qucosaId);
               $mods->addQucosaUrn($urn);
               $document->setXmlData($mods->getModsXml());
          }

          $documentTransferManager = $this->objectManager->get('\EWW\Dpf\Services\Transfer\DocumentTransferManager');
          $remoteRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');
          $documentTransferManager->setRemoteRepository($remoteRepository);

          $objectIdentifier = $document->getObjectIdentifier();

          if (empty($objectIdentifier)) {

            // Document is not in the fedora repository.

            if ($documentTransferManager->ingest($document)) {
              $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_ingest.success';
              $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
              $notifier = $this->objectManager->get('\EWW\Dpf\Services\Email\Notifier');
              $notifier->sendIngestNotification($document);
            } else {
              $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_ingest.failure';
              $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;
            }

          } else {

            // Document needs to be updated.

            if ($documentTransferManager->update($document)) {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.success';
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_update.failure';
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
        public function restoreConfirmAction(\EWW\Dpf\Domain\Model\Document $document) {
            $this->view->assign('document',$document);
        }


        /**
         * action restore
         *
         * @param \EWW\Dpf\Domain\Model\Document $document
         * @return void
         */
        public function restoreAction(\EWW\Dpf\Domain\Model\Document $document) {

            $documentTransferManager = $this->objectManager->get('\EWW\Dpf\Services\Transfer\DocumentTransferManager');
            $remoteRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');
            $documentTransferManager->setRemoteRepository($remoteRepository);


            if ($documentTransferManager->delete($document,"inactivate"))  {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_restore.success';
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_restore.failure';
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
        public function deleteConfirmAction(\EWW\Dpf\Domain\Model\Document $document) {
            $this->view->assign('document',$document);
        }


        /**
         * action delete
         *
         * @param \EWW\Dpf\Domain\Model\Document $document
         * @return void
         */
        public function deleteAction(\EWW\Dpf\Domain\Model\Document $document) {

            $documentTransferManager = $this->objectManager->get('\EWW\Dpf\Services\Transfer\DocumentTransferManager');
            $remoteRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');
            $documentTransferManager->setRemoteRepository($remoteRepository);


            if ($documentTransferManager->delete($document,""))  {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_delete.success';
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_delete.failure';
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
        public function activateConfirmAction(\EWW\Dpf\Domain\Model\Document $document) {
            $this->view->assign('document',$document);
        }


        /**
         * action activate
         *
         * @param \EWW\Dpf\Domain\Model\Document $document
         * @return void
         */
        public function activateAction(\EWW\Dpf\Domain\Model\Document $document) {

            $documentTransferManager = $this->objectManager->get('\EWW\Dpf\Services\Transfer\DocumentTransferManager');
            $remoteRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');
            $documentTransferManager->setRemoteRepository($remoteRepository);


            if ($documentTransferManager->delete($document,"revert"))  {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_activate.success';
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_activate.failure';
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
        public function inactivateConfirmAction(\EWW\Dpf\Domain\Model\Document $document) {
            $this->view->assign('document',$document);
        }


        /**
         * action inactivate
         *
         * @param \EWW\Dpf\Domain\Model\Document $document
         * @return void
         */
        public function inactivateAction(\EWW\Dpf\Domain\Model\Document $document) {

            $documentTransferManager = $this->objectManager->get('\EWW\Dpf\Services\Transfer\DocumentTransferManager');
            $remoteRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');
            $documentTransferManager->setRemoteRepository($remoteRepository);


            if ($documentTransferManager->delete($document,"inactivate"))  {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_inactivate.success';
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_inactivate.failure';
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;
            }

            $this->flashMessage($document, $key, $severity);

            $this->redirect('list');
        }


	// this destroys settings from typoscript inside backend module
	// --> not necessary?
//        public function initializeAction() {
//            parent::initializeAction();
//
//
//            if(TYPO3_MODE === 'BE') {
//                $configManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\BackendConfigurationManager');
//
//                $this->settings = $configManager->getConfiguration(
//                    $this->request->getControllerExtensionName(),
//                    $this->request->getPluginName()
//                );
//           }
//
//		}


        protected function getStoragePID() {
            return $this->settings['persistence']['classes']['EWW\Dpf\Domain\Model\Document']['newRecordStoragePid'];
        }


        /**
         *
         * @param \EWW\Dpf\Domain\Model\Document $document
         * @param string $key
         * @param string $severity
         */
        protected function flashMessage(\EWW\Dpf\Domain\Model\Document $document, $key, $severity) {

             // Show success or failure of the action in a flash message
            $args[] = $document->getTitle();
            $args[] = $document->getObjectIdentifier();

            $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key,'dpf',$args);
            $message = empty($message)? "" : $message;

            $this->addFlashMessage(
                $message,
                '',
                $severity,
                TRUE
            );

        }
}
