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
 * SearchController
 */
class SearchController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

        /**
	 * documentRepository
	 *
	 * @var \EWW\Dpf\Domain\Repository\DocumentRepository
	 * @inject
	 */
	protected $documentRepository = NULL;
        
  
        /**
	 * action list
	 *
	 * @return void
	 */
	public function listAction() {
          
                $objectIdentifiers = $this->documentRepository->getObjectIdentifiers();
                         
		$args = $this->request->getArguments();
		$elasticSearch = new \EWW\Dpf\Services\ElasticSearch();
		// assign result list from elastic search
		$this->view->assign('searchList', $args['results']);
                $this->view->assign('alreadyImported', $objectIdentifiers);

	}

	/**
	 * action search
	 * @return void
	 */
	public function searchAction()
	{
		// perform search action
		$args = $this->request->getArguments();

		$elasticSearch = new \EWW\Dpf\Services\ElasticSearch();
                
                $query = $args['search']['query'];
                                               
                if ($query) {
                  $sessionVars = $GLOBALS["BE_USER"]->getSessionData("tx_dpf");
                  $sessionVars['query'] = $query;
                  $GLOBALS['BE_USER']->setAndSaveSessionData ('tx_dpf', $sessionVars);
                } else {
                   $sessionVars = $GLOBALS['BE_USER']->getSessionData('tx_dpf');
                   $query = $sessionVars['query'];
                }
                
		$results = $elasticSearch->search($query);
                               
		// redirect to list view
		$this->forward("list", NULL, NULL, array('results' => $results));
	}
        
                
        /**
         * action import
         * 
         * @param string $documentObjectIdentifier
         * @return void
         */
        public function importAction($documentObjectIdentifier) {
                                      
          $documentTransferManager = $this->objectManager->get('\EWW\Dpf\Services\Transfer\DocumentTransferManager');
          $remoteRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');                             
          $documentTransferManager->setRemoteRepository($remoteRepository);
          
          $documentTransferManager->retrieve($documentObjectIdentifier);
                     
          $this->redirect('search');    
        } 
                
  
  
}

?>
