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
	 * action delete
	 *
	 * @param \EWW\Dpf\Domain\Model\Document $document
	 * @return void
	 */
	public function deleteAction(\EWW\Dpf\Domain\Model\Document $document) {
                // remove document from local index
                $elasticsearchRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\ElasticsearchRepository');
                // send document to index
                $elasticsearchRepository->delete($document);

                $this->documentRepository->remove($document);
		
                $this->addFlashMessage('The object was deleted.', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);		
              
                $this->redirect('list');
	}
                
        /**
	 * action duplicate
	 *
	 * @param \EWW\Dpf\Domain\Model\Document $document
	 * @return void
	 */
	public function duplicateAction(\EWW\Dpf\Domain\Model\Document $document) {
		$this->addFlashMessage('Das Dokument wurde dupliziert.', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::OK);

                $newDocument = $this->objectManager->get('\EWW\Dpf\Domain\Model\Document');
                
                $newDocument->setTitle($document->getTitle()); 
                $newDocument->setAuthors($document->getAuthors());
                $newDocument->setxmlData($document->getXmlData());
                $newDocument->setDocumentType($document->getDocumentType());
                $newDocument->removeDateIssued();
                
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
          if (!$mods->hasUrn() ) {        
               $identifierUrn = new \EWW\Dpf\Services\Identifier\IdentifierUrn("a","b","c");  
               $urn = $identifierUrn->getUrn($qucosaId);
               $mods->addUrn($urn);
               $document->setXmlData($mods->getModsXml());
          }                                
            
          
          var_dump($document);
          
          die("test");
                                
          $documentTransferManager = $this->objectManager->get('\EWW\Dpf\Services\Transfer\DocumentTransferManager');
          $remoteRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');                             
          $documentTransferManager->setRemoteRepository($remoteRepository);
                              
          $objectIdentifier = $document->getObjectIdentifier();
          
          if (empty($objectIdentifier)) {         
          
            // Document is not in the fedora repository.
            
            $args[] = $document->getTitle();     
         
            if ($documentTransferManager->ingest($document)) {
              $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_ingest.success';
              $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
            } else {
              $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_ingest.failure';
              $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;
            }
                                                    
          } else {                                  
            
            $args[] = $document->getTitle();
            $args[] = $document->getObjectIdentifier(); 
                     
            if ($document->getRemoteAction() == \EWW\Dpf\Domain\Model\Document::REMOTE_ACTION_DELETE) {
              
              // Document is marked for deletion.
              
              if ($documentTransferManager->delete($document))  {              
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_delete.success';
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
              } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_delete.failure';    
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
                                               
          }
          
         
          // Show success or failure of the action in a flash message
          
          $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key,'dpf',$args);
          $message = empty($message)? "" : $message;
         
          $this->addFlashMessage(
            $message,
            '',
            $severity,
            TRUE
          );
                              
          $this->redirect('list');          
        }   
        
        
        public function initializeAction() {
            parent::initializeAction();
                               
            if ( !$GLOBALS['BE_USER'] ) {
                 throw new \Exception('Access denied');
            }      
            
        }    
                                            
}