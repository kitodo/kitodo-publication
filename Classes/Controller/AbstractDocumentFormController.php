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
 * DocumentFormController
 */
abstract class AbstractDocumentFormController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * documentRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\DocumentRepository
	 * @inject
	 */
	protected $documentRepository = NULL;
        
        
        /**
	 * fileRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\FileRepository
	 * @inject
	 */
	protected $fileRepository = NULL;
        
        
        /**
	 * documentTypeRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
	 * @inject
	 */
	protected $documentTypeRepository = NULL;        


        /**
	 * metadataGroupRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
	 * @inject
	 */
	protected $metadataGroupRepository = NULL;

        
         /**
	 * metadataObjectRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\MetadataObjectRepository
	 * @inject
	 */
	protected $metadataObjectRepository = NULL;
        
        
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
                
                $documentTypes = $this->documentTypeRepository->findAll();
                                
                
                $this->view->assign('listtype', $this->settings['listtype']);
                
                $this->view->assign('documentTypes', $documentTypes);                                
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
         * initialize newAction
         * 
         * @return void
         */
        public function initializeNewAction() {
           
          $requestArguments = $this->request->getArguments();   
          
          if (array_key_exists('documentData', $requestArguments)) {            
            $documentData = $this->request->getArgument('documentData');  
            $formDataReader = $this->objectManager->get('EWW\Dpf\Helper\FormDataReader');
            $formDataReader->setFormData($documentData);
            $docForm = $formDataReader->getDocumentForm();
          } elseif (array_key_exists('documentType', $requestArguments)) {                    
            $docTypeUid = $this->request->getArgument('documentType');          
            $documentType = $this->documentTypeRepository->findByUid($docTypeUid);                
            $document = $this->objectManager->get('\EWW\Dpf\Domain\Model\Document'); 
            $document->setDocumentType($documentType);
            $mapper = $this->objectManager->get('EWW\Dpf\Helper\DocumentMapper');             
            $docForm = $mapper->getDocumentForm($document);
          } elseif (array_key_exists('newDocumentForm', $requestArguments)) {                                               
            $docForm = $this->request->getArgument('newDocumentForm');                              
          }
          
          $requestArguments['newDocumentForm'] = $docForm;
          $this->request->setArguments($requestArguments);                       
        }
        
        
	/**
	 * action new
	 *
	 * @param \EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm
	 * @ignorevalidation $newDocumentForm
	 * @return void
	 */
	public function newAction(\EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm = NULL) {                      
                $this->view->assign('documentForm', $newDocumentForm);            
	}

              
        public function initializeCreateAction() {
        
            $requestArguments = $this->request->getArguments();                                                                 
        
            $documentData = $this->request->getArgument('documentData');
                        
            $formDataReader = $this->objectManager->get('EWW\Dpf\Helper\FormDataReader');
            $formDataReader->setFormData($documentData);
            $docForm = $formDataReader->getDocumentForm();
            
            $requestArguments['newDocumentForm'] = $docForm;
            $this->request->setArguments($requestArguments);                                                   
        }
        
        
	/**
	 * action create
	 *
	 * @param \EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm
	 * @return void
	 */
	public function createAction(\EWW\Dpf\Domain\Model\DocumentForm $newDocumentForm) {
                           
          $documentMapper = $this->objectManager->get('EWW\Dpf\Helper\DocumentMapper');
          $newDocument = $documentMapper->getDocument($newDocumentForm);      
          //$newDocument->setRemoteAction(\EWW\Dpf\Domain\Model\Document::REMOTE_ACTION_INGEST);
          $this->documentRepository->add($newDocument);
          $this->persistenceManager->persistAll();

          $newDocument = $this->documentRepository->findByUid( $newDocument->getUid());
          $this->persistenceManager->persistAll();
         
 /*         
          // Delete files 
          foreach ( $newDocumentForm->getDeletedFiles() as $deleteFile ) {              
            $deleteFile->setStatus( \EWW\Dpf\Domain\Model\File::STATUS_DELETED);
            $this->fileRepository->update($deleteFile);
          }
  */
          
          // Add new files
          foreach ( $newDocumentForm->getNewFiles() as $newFile ) {                          
            $newFile->setDocument($newDocument);
            $this->fileRepository->add($newFile);                       
            //$newDocument->addFile($newFile);           
          }
          
                                                    
          $requestArguments = $this->request->getArguments();                                                                         
                    
          if (array_key_exists('savecontinue', $requestArguments)) {            
            $this->forward('new',NULL,NULL,array('newDocumentForm' => $newDocumentForm));                        
          }                             
          
          $this->redirectToList();
	}

                
        public function initializeEditAction() {
                                                            
          $requestArguments = $this->request->getArguments();
          
          if (array_key_exists('document', $requestArguments)) {                          
            $documentUid = $this->request->getArgument('document');            
            $document = $this->documentRepository->findByUid($documentUid);                                                           
            $mapper = $this->objectManager->get('EWW\Dpf\Helper\DocumentMapper');                                               
            $documentForm = $mapper->getDocumentForm($document);                        
          } elseif (array_key_exists('documentForm', $requestArguments)) {                                               
            $documentForm = $this->request->getArgument('documentForm');                              
          }
           
          $requestArguments['documentForm'] = $documentForm;
          $this->request->setArguments($requestArguments);                                                        
        }
               
        
	/**
	 * action edit
	 *
	 * @param \EWW\Dpf\Domain\Model\DocumentForm $documentForm
	 * @ignorevalidation $documentForm
	 * @return void
	 */
	public function editAction(\EWW\Dpf\Domain\Model\DocumentForm $documentForm) {                                                            
          $this->view->assign('documentForm', $documentForm);                                                                    
	}

        
        public function initializeUpdateAction() {          
            $requestArguments = $this->request->getArguments();                                                                 
        
            $documentData = $this->request->getArgument('documentData');
                                   
            $formDataReader = $this->objectManager->get('EWW\Dpf\Helper\FormDataReader');
            $formDataReader->setFormData($documentData);
            $docForm = $formDataReader->getDocumentForm();
            
            $requestArguments['documentForm'] = $docForm;
            $this->request->setArguments($requestArguments);                                                    
        }
        
        
	/**
	 * action update
	 *
	 * @param \EWW\Dpf\Domain\Model\DocumentForm $documentForm
	 * @return void
	 */
	public function updateAction(\EWW\Dpf\Domain\Model\DocumentForm $documentForm) {
            
          $requestArguments = $this->request->getArguments();                                                                         
            
          $documentMapper = $this->objectManager->get('EWW\Dpf\Helper\DocumentMapper');
          $updateDocument = $documentMapper->getDocument($documentForm);    
                                                  
          $objectIdentifier = $updateDocument->getObjectIdentifier();
          
          if (empty($objectIdentifier)) {
            $updateDocument->setRemoteAction(\EWW\Dpf\Domain\Model\Document::REMOTE_ACTION_INGEST);    
          } else { 
            $updateDocument->setRemoteAction(\EWW\Dpf\Domain\Model\Document::REMOTE_ACTION_UPDATE);             
          }
          
          $this->documentRepository->update($updateDocument);        
                    
          
          // Delete files 
          foreach ( $documentForm->getDeletedFiles() as $deleteFile ) {          
            $deleteFile->setStatus(\EWW\Dpf\Domain\Model\File::STATUS_DELETED);
            $this->fileRepository->update($deleteFile);
          }
                    
          // Add new files
          foreach ( $documentForm->getNewFiles() as $newFile ) {     
            $updateDocument->addFile($newFile);           
          }
                    
                                                               
          
          if (array_key_exists('savecontinue', $requestArguments)) {            
            $this->forward('edit',NULL,NULL,array('documentForm' => $documentForm));                        
          }      
                                                                    
          $this->redirectToList();
	}
        
	                                                                                          
    public function initializeAction() {
      parent::initializeAction();
                                           
      $requestArguments = $this->request->getArguments();                              
                     
      if ($requestArguments['cancel']) {         
        $this->redirectToList();         
      }
    }    
    
    
    protected function redirectToList() {
      $this->redirect('list');  
    }

}