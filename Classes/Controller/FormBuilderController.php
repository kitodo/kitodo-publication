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
 * FormBuilderController
 */
class FormBuilderController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * documentTypeRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\DocumentTypeRepository
	 * @inject
	 */
	protected $documentTypeRepository = NULL;


        /**
	 * metadataPageRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\MetadataPageRepository
	 * @inject
	 */
	protected $metadataPageRepository = NULL;


        /**
	 * MetadataGroupRepository
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
	 * documentRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\DocumentRepository
	 * @inject
	 */
	protected $documentRepository = NULL;

        
        
	/**
	 * action newDocument
	 *
	 * @return void
	 */
	public function selectAction() {
            
            
           $documentTypes = $this->documentTypeRepository->findAll();
            
           $docTypes = array();
            foreach ($documentTypes as $documentType) {              
              $docTypes[$documentType->getUid()] = $documentType->getDisplayname();              
            }
          
          $this->view->assign('docTypes', $docTypes);
           // $this->redirect('new',NULL,NULL,array('dtuid'=>$docTypeUid));
            
        }

	/**
	 * action new
	 *
         * @param integer $document
         * @param array $documentType
	 * @return void
	 */
	public function newAction( integer $document = NULL, array $documentType = NULL ) {
                                                                  
            $docTypeUid =  $documentType['id']; //$this->settings['documenttype'];
            
            $documentType = $this->documentTypeRepository->findByUid($docTypeUid);

            $basicForm = \EWW\Dpf\Helper\FormFactory::createForm($documentType);
            

            if (!$documentType) {
                $this->addFlashMessage('Es wurde kein Dokument-Typ angegeben.');
            } else {

                if (!$document) {
                        $qucosaForm = \EWW\Dpf\Helper\FormFactory::createForm($documentType);
                } else {
   
                    $document = $this->documentRepository->findByUid($document);
                    if ($document) {
                        $qucosaForm = \unserialize($document->getXmlData());

                    }
                   
                }
            }
            
            $this->view->assign('basicForm', $basicForm);
            $this->view->assign('qucosaForm', $qucosaForm);
               
	}


        /**
         * action create
         *
         * @param array $newDocument
         * @param integer $documentType
         * @return void
         */
        public function createAction( array $newDocument = NULL, integer $documentType = NULL ) {

                 $files = $newDocument['files']; 
                 unset($newDocument['files']); 
                 $data = $newDocument;

//                 $this->view->assign('debugData', $documentType);

                 foreach ($data as $key => $value) {
                    if (!$value) {
                        unset($data[$key]);
                    }                     
                 }

                 
                 $docTypeUid = $documentType;
                 $documentType = $this->documentTypeRepository->findByUid($docTypeUid);
                 $formStructure = \EWW\Dpf\Helper\FormFactory::createFormDataArray($documentType);

      //$this->view->assign('debugData', $documentType); 
                 $formData = $formStructure;
                 foreach ($data as $key => $value) {

                    $field_id = split("-", $key);
                  
                    $pageUid = $field_id[0];
                    $pageNumber = $field_id[1];

                    $groupUid = $field_id[2];
                    $groupNumber = $field_id[3];

                    $fieldUid = $field_id[4];
                    $fieldNumber = $field_id[5];


                    if (!key_exists($pageUid, $formData)) {
                        $formData[$pageUid] = $formStructure[$pageUid];
                    }

                    if (!key_exists($pageNumber, $formData[$pageUid])) {
                        $formData[$pageUid][$pageNumber] = $formStructure[$pageUid][0];
                    }

                    if (!key_exists($groupUid, $formData[$pageUid][$pageNumber])) {
                        $formData[$pageUid][$pageNumber][$groupUid] = $formStructure[$pageUid][0][$groupUid];
                    }

                    if (!key_exists($groupNumber, $formData[$pageUid][$pageNumber][$groupUid])) {
                        $formData[$pageUid][$pageNumber][$groupUid][$groupNumber] = $formStructure[$pageUid][0][$groupUid][0];
                    }

                    if (!key_exists($fieldUid, $formData[$pageUid][$pageNumber][$groupUid][$groupNumber])) {
                        $formData[$pageUid][$pageNumber][$groupUid][$groupNumber][$fieldUid] = $formStructure[$pageUid][0][$groupUid][0][$fieldUid];
                    }

                    if (!key_exists($fieldNumber, $formData[$pageUid][$pageNumber][$groupUid][$groupNumber][$fieldUid])) {
                        $formData[$pageUid][$pageNumber][$groupUid][$groupNumber][$fieldUid][$fieldNumber] = $formStructure[$pageUid][0][$groupUid][0][$fieldUid][0];
                    }

                    $formData[$pageUid][$pageNumber][$groupUid][$groupNumber][$fieldUid][$fieldNumber] = $value;

                    
                 }
                               
                if ($this->request->hasArgument('cancel') || empty($data)) {

                  $this->redirect('select');

                } else {

                    $docTypeUid = $this->settings['documenttype'];

                    $formFactory = new \EWW\Dpf\Helper\FormFactory(
                            $this->documentTypeRepository,
                            $this->metadataPageRepository,
                            $this->metadataGroupRepository,
                            $this->metadataObjectRepository);

                    $qucosaForm = $formFactory->createFromDataArray($formData, $docTypeUid);

                    $document = new \EWW\Dpf\Domain\Model\Document();
                    $document->setXmlData(serialize($qucosaForm));

                    $this->documentRepository->add($document);

                    $persistenceManager = $this->objectManager->get('Tx_Extbase_Persistence_Manager');
                    $persistenceManager->persistAll();

                    $this->addFlashMessage('The object was created. Please be aware that this action is publicly accessible unless you implement an access check. See <a href="http://wiki.typo3.org/T3Doc/Extension_Builder/Using_the_Extension_Builder#1._Model_the_domain" target="_blank">Wiki</a>', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);


                    if ($this->request->hasArgument('savecontinue')) {

                        $this->redirect('new',NULL,NULL,array('document'=>$document->getUid(), 'documentType' => array( 'id' => $docTypeUid)));

                    } else {


                        $this->redirect('new');
                    }

                }

               // $this->view->assign('debugData', $formData);
        
                
             }
 
       
}