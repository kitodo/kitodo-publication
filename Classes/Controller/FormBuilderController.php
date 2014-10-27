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
	 * action new
	 *
         * @param array $newDocument
	 * @return void
	 */
	public function newAction( array $newDocument = NULL ) {
            
                $docTypeUid = $this->settings['documenttype'];

                if (!$newDocument) {
                  
                    if ($docTypeUid) {
                        $documentType = $this->documentTypeRepository->findByUid($docTypeUid);
                        $qucosaForm = \EWW\Dpf\Helper\FormFactory::createForm($documentType);
                    }

                } else {

                    $formFactory = new \EWW\Dpf\Helper\FormFactory(
                            $this->documentTypeRepository,
                            $this->metadataPageRepository,
                            $this->metadataGroupRepository,
                            $this->metadataObjectRepository);

                    $qucosaForm = $formFactory->createFromDataArray($newDocument, $docTypeUid);
                   
                }

             /*$document = $this->documentRepository->findByUid();
             if ($document) {
                  $qucosaForm = \unserialize($document->getXmlData());
                 
             }
             */              
                    
             $this->view->assign('qucosaForm', $qucosaForm);
                
	}


        /**
         * action create
         *
         * @param array $newDocument
         * @return void
         */
        public function createAction( array $newDocument ) {
                $this->addFlashMessage('The object was created. Please be aware that this action is publicly accessible unless you implement an access check. See <a href="http://wiki.typo3.org/T3Doc/Extension_Builder/Using_the_Extension_Builder#1._Model_the_domain" target="_blank">Wiki</a>', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);

                if ($this->request->hasArgument('cancel')) {

                  $this->redirect('new');
                  
                } else {

                    $docTypeUid = $this->settings['documenttype'];

                    $formFactory = new \EWW\Dpf\Helper\FormFactory(
                            $this->documentTypeRepository,
                            $this->metadataPageRepository,
                            $this->metadataGroupRepository,
                            $this->metadataObjectRepository);

                    $qucosaForm = $formFactory->createFromDataArray($newDocument, $docTypeUid);

                    $document = new \EWW\Dpf\Domain\Model\Document();
                    $document->setXmlData(serialize($qucosaForm));

                    $this->documentRepository->add($document);

                    if ($this->request->hasArgument('savecontinue')) {

                        $this->redirect('new',NULL,NULL,array('newDocument'=>$newDocument));

                    } else {

                        $this->redirect('new');
                    }
                    
                }
            
        }

}