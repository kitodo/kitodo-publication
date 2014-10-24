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
	 * metadataGroupRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\MetadataGroupRepository
	 * @inject
	 */
	protected $metadataGroupRepository = NULL;
	
        /**
	 * formPageRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\FormPageRepository
	 * @inject
	 */
	protected $formPageRepository = NULL;

        
	/**
	 * action list
	 *
	 * @return void
	 */
	public function listAction() {


                //$test = $this->objectManager->get('\\GK\\Stdapp\\Domain\\Repository\\FormPageRepository');

          	$docTypeUid = $this->settings['documenttype'];

                $documentType = $this->documentTypeRepository->findByUid($docTypeUid);
                
                $formPages = $documentType->getFormPage();
                                
                $qucosaForm = new \EWW\Dpf\Helper\FormNode();                   
                $qucosaForm->setName($documentType->getTitle());
                               
                
                // Form pages
                foreach ($formPages as $formPage) {
                  
                  $formPageNode = new \EWW\Dpf\Helper\FormNode();     
                  $formPageNode->setName($formPage->getDisplayTitle());                  
                                                                         
                  $metadataGroups = $formPage->getMetadataGroup();                                                     
                                   
                  
                  // Metadata groups
                  foreach ($metadataGroups as $metadataGroup) {
                                        
                    $metadataGroupNode = new \EWW\Dpf\Helper\FormNode();     
                    $metadataGroupNode->setName($metadataGroup->getTitle());           
                                                            
                    
                    $metadataObjects = $metadataGroup->getMetadataObject();
                                    
                    // Metadata objects / fields
                    foreach ($metadataObjects as $metadataObject) {
                      
                      $metadataObjectNode = new \EWW\Dpf\Helper\FormNode();     
                      $metadataObjectNode->setName($metadataObject->getTitle());
                      
                      $metadataGroupNode->addChild($metadataObjectNode);
                      
                    }
                    
                    
                    $formPageNode->addChild($metadataGroupNode);
                    
                  }
                  
                  $qucosaForm->addChild($formPageNode);                  
                }

                                                                
                $this->view->assign('qucosaForm', $qucosaForm);	
                                
                        //$this->metadataGroupRepository->findByDocumentType();
		//$this->documentTypeRepository->findByDocumentType();
		
		//if ( $this->settings['documenttype'] ) {	
		//	$documentTypes = $this->documentTypeRepository->findAll();
		//	$this->view->assign('documentTypes', $documentTypes);
		// }	
	}

}