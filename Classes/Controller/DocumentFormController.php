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
class DocumentFormController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * documentRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\DocumentRepository
	 * @inject
	 */
	protected $documentRepository = NULL;
        
        
        /**
	 * documentTypeRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
	 * @inject
	 */
	protected $documentTypeRepository = NULL;        
        

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
          
                $documentType = $this->documentTypeRepository->findByUid(1);
                $document = $this->documentRepository->findByUid(28);
               
                $mapper = new \EWW\Dpf\Helper\DocumentFormMapper();
                //$mapper->setDocument($document);
                $this->view->assign('documentForm', $mapper->getDocumentForm($documentType,$document));
          
		//$this->view->assign('newDocument', $newDocument);
	}

      
	/**
	 * action create
	 *
	 * @param array $newDocument
	 * @return void
	 */
	public function createAction(array $newDocument) {
		//$this->addFlashMessage('The object was created. Please be aware that this action is publicly accessible unless you implement an access check. See <a href="http://wiki.typo3.org/T3Doc/Extension_Builder/Using_the_Extension_Builder#1._Model_the_domain" target="_blank">Wiki</a>', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
		//$this->documentRepository->add($newDocument);	       


                $documentType = $this->documentTypeRepository->findByUid($newDocument['type']);

                $mapper = new \EWW\Dpf\Helper\DocumentFormMapper();


                
                $newDocument = $mapper->getDocumentData($documentType,$newDocument);

                foreach ($newDocument['files'] as $tmpFile ) {
                                                      
                  $path = "uploads/tx_dpf";
                  $fileName = $path."/".time()."_".rand()."_".$tmpFile['name']; 
                  
                  if (\TYPO3\CMS\Core\Utility\GeneralUtility::upload_copy_move($tmpFile['tmp_name'],$fileName) ) {                    
                     $file['type'] = $mimeType = $tmpFile['type'];  
                     $file['path'] = $fileName;
                  
                     $files[] = $file;
                     
                  }
                                                                       
                }
                
                $newDocument['files'] = $files;
                
                
                $exporter = new \EWW\Dpf\Services\MetsExporter();                
                $exporter->buildModsFromForm($newDocument);
                
                $newDocument1 = $exporter->getMetsData();
	        
                $newDoc = new \EWW\Dpf\Domain\Model\Document();
                $newDoc->setXmlData($newDocument1);                
                
                $this->documentRepository->add($newDoc);
                
                
                $this->view->assign('newDocument', $newDocument);
                
                //  $this->redirect('list');
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
		$this->addFlashMessage('The object was deleted. Please be aware that this action is publicly accessible unless you implement an access check. See <a href="http://wiki.typo3.org/T3Doc/Extension_Builder/Using_the_Extension_Builder#1._Model_the_domain" target="_blank">Wiki</a>', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
		$this->documentRepository->remove($document);
		$this->redirect('list');
	}
        
        
        /**
         * 
         * @param string $partialName
         * @param array $data
         * @return string
         * @throws \TYPO3\CMS\Extbase\Mvc\Exception\RequiredArgumentMissingException
         */
        private function renderPartial($partialName='', $data = array()){
            if($partialName==''){
                throw new \TYPO3\CMS\Extbase\Mvc\Exception\RequiredArgumentMissingException('The Partial name must be defined.', 123456789);
            }
            $templateView = $this->objectManager->create('TYPO3\\CMS\\Fluid\\View\\TemplateView');
            
            
            //var_dump($this->templateView); die();
            
            //$this->templateView = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Fluid\View\TemplateView');
   
          
            $res = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($this->controllerContext->getRequest()->getControllerExtensionKey()) . 'Resources/Private/';
            $templateView->setLayoutRootPath($res);
            $templateView->setPartialRootPath($res . 'Partials/');
            //$templateView->setRenderingContext($this->objectManager->create('TYPO3\\CMS\\Fluid\\Core\\Rendering\\RenderingContext'));
            $templateView->setControllerContext($this->controllerContext);

            return $templateView->renderPartial($partialName, Null, $data);
        }

           
        
        public function getGroupAction() {           
          return $this->renderPartial("DocumentForm/Group",array());                    
        }
        
}