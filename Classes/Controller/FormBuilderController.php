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
	 * action new
	 *
         * @param \EWW\Dpf\Helper\Form $newForm
	 * @return void
	 */
	public function newAction( \EWW\Dpf\Helper $newForm=NULL) {

          	$docTypeUid = $this->settings['documenttype'];

                if ($docTypeUid) {

                    $documentType = $this->documentTypeRepository->findByUid($docTypeUid);
                
                    $qucosaForm = \EWW\Dpf\Helper\FormFactory::createForm($documentType);
                                                                
                    $this->view->assign('qucosaForm', $qucosaForm);

                }
                
	}


        /**
         * action create
         *
         * @param \EWW\Dpf\Helper $newForm
         * @return void
         */
        public function createAction( \EWW\Dpf\Helper\Form $newForm ) {
                $this->addFlashMessage('The object was created. Please be aware that this action is publicly accessible unless you implement an access check. See <a href="http://wiki.typo3.org/T3Doc/Extension_Builder/Using_the_Extension_Builder#1._Model_the_domain" target="_blank">Wiki</a>', '', \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR);
		//$this->documentTypeRepository->add($newDocumentType);		
                //$this->view->assign('qucosaForm', $qucosaForm);

                $this->redirect('new');
            
        }


}