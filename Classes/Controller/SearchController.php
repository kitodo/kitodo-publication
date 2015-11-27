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
class SearchController extends \EWW\Dpf\Controller\AbstractController
{
    /**
     * documentRepository
     *
     * @var \EWW\Dpf\Domain\Repository\DocumentRepository
     * @inject
     */
    protected $documentRepository = null;

    /**
    * clientRepository
    *
    * @var \EWW\Dpf\Domain\Repository\ClientRepository
    * @inject
    */
    protected $clientRepository = null;

        /**
     * action list
     *
     * @return void
     */
    public function listAction()
    { 
        $objectIdentifiers = $this->documentRepository->getObjectIdentifiers();

        $args = $this->request->getArguments();
        $elasticSearch = new \EWW\Dpf\Services\ElasticSearch();
        // assign result list from elastic search        
        $this->view->assign('searchList', $args['results']);
        $this->view->assign('alreadyImported', $objectIdentifiers);
    }

    /**
     * get next search results
     * @return array ElasticSearch results
     */
    public function nextResultsAction()
    {
        $sessionVars = $GLOBALS["BE_USER"]->getSessionData("tx_dpf");
        if (!$sessionVars['resultCount']) {
            // set number of results in session
            $sessionVars['resultCount'] = 50;
        } else {
            $resultCount = $sessionVars['resultCount'];
            $sessionVars['resultCount'] = $resultCount + 50;
        }
        $GLOBALS['BE_USER']->setAndSaveSessionData('tx_dpf', $sessionVars);

        $query = $sessionVars['query'];
             
        $query['from'] = $sessionVars['resultCount'];
        $query['size'] = 5;
        
       
        $results = $this->getResultList($query);

       //  echo "<pre>";
       // var_dump($results);
       // echo "</pre>";
        
        $this->view->assign('resultList', $results);
    }

    /**
     * build array for elasticsearch
     * @return array Elasticsearch query array
     */
    public function extendedSearch()
    {
        $args = $this->request->getArguments();
        $client = $this->clientRepository->findAll()->current();

        // extended search
        $countFields = 0;

        if ($args['extSearch']['extId']) {
            $id = $args['extSearch']['extId'];
            $fieldQuery['_id'] = $id;
            $countFields++;
        }

        if ($args['extSearch']['extTitle']) {
            $title = $args['extSearch']['extTitle'];
            $fieldQuery['title'] = $title;
            $countFields++;
        }

        if ($args['extSearch']['extAuthor']) {
            $author = $title = $args['extSearch']['extAuthor'];
            $fieldQuery['author'] = $author;
            $countFields++;
        }

        if ($args['extSearch']['extDeleted']) {
            // STATE deleted
            $delete['bool']['must'][] = array('match' => array('STATE' => 'D'));
            // STATE inactive
            $inactive['bool']['must'][] = array('match' => array('STATE' => 'I'));

            $query['body']['query']['bool']['should'][] = $delete;
            $query['body']['query']['bool']['should'][] = $inactive;

            $query['body']['query']['bool']['minimum_should_match'] = 1;

        } else {
            // STATE active
            $deleted = true;
            $fieldQuery['STATE'] = 'A';
            $countFields++;
        }

        if ($countFields >= 1) {
            // multi field search
            $i = 1;
            foreach ($fieldQuery as $key => $qry) {
                $query['body']['query']['bool']['must'][] = array('match' => array($key => $qry));
                $i++;
            }
        }

        // owner id
        $query['body']['query']['bool']['must'][] = array('match' => array('OWNER_ID' => $client->getOwnerId()));

        return $query;
    }

    public function searchFulltext()
    {
        // perform fulltext search
        $args = $this->request->getArguments();
                                        
        $client = $this->clientRepository->findAll()->current();
        
        // dont return query if keys not existing
        if ( !key_exists('search', $args) || !key_exists('query',$args['search'])) {            
            return NULL;
        }

        $searchText = $this->escapeQuery($args['search']['query']);

        // add owner id
        $query['body']['query']['bool']['must']['term']['OWNER_ID'] = $client->getOwnerId(); // qucosa

        $query['body']['query']['bool']['should'][0]['query_string']['query'] = $searchText;
        $query['body']['query']['bool']['should'][1]['has_child']['query']['query_string']['query'] = $searchText;

        $query['body']['query']['bool']['minimum_should_match'] = "1"; // 1

        $query['body']['query']['bool']['should'][1]['has_child']['child_type'] = "datastream"; // 1

        // $query['body']['query']['fields'][0] = "PID";
        // $query['body']['query']['fields'][1] = "_dissemination._content.PUB_TITLE";
        // $query['body']['query']['fields'][2] = "_dissemination._content.PUB_AUTHOR";
        // $query['body']['query']['fields'][3] = "_dissemination._content.PUB_DATE";
        // $query['body']['query']['fields'][4] = "_dissemination._content.PUB_TYPE";

        return $query;

    }

    public function escapeQuery($string)
    {
        $luceneReservedCharacters = preg_quote('+-&|!(){}[]^"~?:\\');
        $string = preg_replace_callback(
            '/([' . $luceneReservedCharacters . '])/',
            function($matches) {
                return '\\' . $matches[0];
            },
            $string
        );

        return $string;
    }

    /**
     * search
     * @return array elasticsearch query
     */
    public function search()
    {
        // perform search action
        $args = $this->request->getArguments();

        $client = $this->clientRepository->findAll()->current();
        if (empty($args['search']['query'])) {
            // elasticsearch dsl requires an empty object to match all
            $query['body']['query']['match_all'] = new \stdClass();
        } else {
            $query['body']['query']['match']['_all'] = $args['search']['query'];
        }
        
        return $query;
    }

    /**
     * get results from elastic search
     * @param  array $query elasticsearch search query
     * @return array        results
     */
    public function getResultList($query, $type)
    {
        $elasticSearch = new \EWW\Dpf\Services\ElasticSearch();

     //   die();
        $results = $elasticSearch->search($query, $type);

        return $results;
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

        // reset session pagination
        $sessionVars = $GLOBALS['BE_USER']->getSessionData('tx_dpf');
        $sessionVars['resultCount'] = 50;
        $GLOBALS['BE_USER']->setAndSaveSessionData('tx_dpf', $sessionVars);
               
        // set sorting
        // $query['body']['sort']['PID']['order'] = 'asc';
        if ($args['extSearch']) {
            // extended search
            $query = $this->extendedSearch();
        } else {
            $query = $this->searchFulltext();
        }
        
        // save search query
        if ($query) {                                   
            $query['body']['from'] = '0';
            $query['body']['size'] = '50';           
            $sessionVars = $GLOBALS["BE_USER"]->getSessionData("tx_dpf");
            $sessionVars['query'] = $query;
            $GLOBALS['BE_USER']->setAndSaveSessionData('tx_dpf', $sessionVars);                       
        } else {
            $sessionVars = $GLOBALS['BE_USER']->getSessionData('tx_dpf');                                 
            $query = $sessionVars['query'];             
        }

        // set type local vs object
        $type = 'object';
        
        $results = $this->getResultList($query, $type);

        // redirect to list view
        $this->forward("list", null, null, array('results' => $results));
    }

    /**
     * action import
     *
     * @param  string $documentObjectIdentifier
     * @param  string $objectState
     * @return void
     */
    public function importAction($documentObjectIdentifier, $objectState)
    {
        $documentTransferManager = $this->objectManager->get('\EWW\Dpf\Services\Transfer\DocumentTransferManager');
        $remoteRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');
        $documentTransferManager->setRemoteRepository($remoteRepository);

        $args[] = $documentObjectIdentifier;

        if ($documentTransferManager->retrieve($documentObjectIdentifier)) {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_retrieve.success';
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
        } else {
            $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_retrieve.failure';
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;
        }

        // Show success or failure of the action in a flash message

        $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf', $args);
        $message = empty($message) ? "" : $message;

        $this->addFlashMessage(
            $message,
            '',
            $severity,
            true
        );

                
        $this->forward('updateIndex',NULL,NULL,array('documentObjectIdentifier' => $documentObjectIdentifier));   
        
        //$this->redirect('search');
    }
    
    /**
     * 
     * @param  string $documentObjectIdentifier
     * @return void
     */
    public function updateIndexAction($documentObjectIdentifier)
    {
        $document = $this->documentRepository->findByObjectIdentifier($documentObjectIdentifier);
        
        if (is_a($document,'\EWW\Dpf\Domain\Model\Document')) {
            $elasticsearchRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\ElasticsearchRepository');
            $elasticsearchMapper = $this->objectManager->get('EWW\Dpf\Helper\ElasticsearchMapper');
            $json = $elasticsearchMapper->getElasticsearchJson($document);
            // send document to index
            $elasticsearchRepository->add($document, $json);
        }
        
        $this->redirect('search');        
    }
    
    
    /**
     * action doubletCheck
     *
     * @param  \EWW\Dpf\Domain\Model\Document $document
     * @return void
     */
    public function doubletCheckAction(\EWW\Dpf\Domain\Model\Document $document)
    {
        $elasticSearch = new \EWW\Dpf\Services\ElasticSearch();

        $client = $this->clientRepository->findAll()->current();

        // es source fields
        // title
        // abstract
        // author
        // language
        // publisher
        // publisher_place
        // distributor
        // distributor_place
        // distributor_date
        // classification
        // tag
        // identifier
        // submitter
        // project
        
        // is doublet existing?
        $query['body']['query']['bool']['must'][]['match']['title'] = $document->getTitle();
        // $query['body']['query']['bool']['must'][]['match']['author'] = $document->getAuthors()[0];

        // set owner id
        $query['body']['query']['bool']['must'][]['term']['OWNER_ID'] = $client->getOwnerId();

        $results = $elasticSearch->search($query);

        // redirect to list view
        //$this->forward("list", null, null, array('results' => $results));
        
        $objectIdentifiers = $this->documentRepository->getObjectIdentifiers();

        $args = $this->request->getArguments();
        $elasticSearch = new \EWW\Dpf\Services\ElasticSearch();
      
        $this->view->assign('document', $document);
        $this->view->assign('searchList', $results);
        $this->view->assign('alreadyImported', $objectIdentifiers);
        
    }

    /**
     * action show preview
     * 
     * @param  string $documentObjectIdentifier
     * @return void
     */
    public function showPreviewAction($documentObjectIdentifier) {
                                                                                      
        $baseURL = stripos($_SERVER['SERVER_PROTOCOL'], 'https') === true ? 'https://' : 'http://';
        $port = '';
        if ($_SERVER['SERVER_PORT'] && intval($_SERVER['SERVER_PORT']) != 80) {
            $port = ':'.$_SERVER['SERVER_PORT'];
        }
        $baseURL .= trim($_SERVER['SERVER_NAME'], "/").$port."/";
          
        // realurl inactive
        //$metsURL = $baseURL . "index.php?type=110125&tx_dpf_qucosaxml[action]=previewData&tx_dpf_qucosaxml[docId]=".$document->getUid();  

        // realurl active
        // $metsURL = $baseURL . "api/action/previewData/id/".$documentObjectIdentifier;
        $confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dpf']);
        $metsURL = $confArr['fedoraHost'].'/fedora/objects/'.$documentObjectIdentifier.'/methods/qucosa:SDef/getMETSDissemination';
       
        
        $configManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\BackendConfigurationManager');
 
                $settings = $configManager->getConfiguration(
                  $this->request->getControllerExtensionName(),
                  $this->request->getPluginName()
                );               
        
        $previewPage = $settings['settings']['previewPage'];
                      
        if (is_numeric($previewPage)) {
            $previewUri = $baseURL."index.php?id=".$previewPage."&tx_dlf_document_url=".urlencode($metsURL);
        } else {
            $previewPage = trim($previewPage, "/?&# ");
            
            $url = parse_url($previewPage);
                    
            $previewUri = $previewPage.(empty($url['query'])?"?":"&")."tx_dlf_document_url=".urlencode($metsURL);
        }
        
        $this->redirectToUri($previewUri);
    }
                      
}
