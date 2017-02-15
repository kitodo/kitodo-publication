<?php
namespace EWW\Dpf\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * SearchController
 */
class SearchController extends \EWW\Dpf\Controller\AbstractSearchController
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

    const RESULT_COUNT      = 50;
    const NEXT_RESULT_COUNT = 50;

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        $objectIdentifiers = $this->documentRepository->getObjectIdentifiers();

        $args          = $this->request->getArguments();
        $elasticSearch = new \EWW\Dpf\Services\ElasticSearch();
        // assign result list from elastic search
        $this->view->assign('searchList', $args['results']);
        $this->view->assign('alreadyImported', $objectIdentifiers);

        // assign form values
        $this->assignExtraFields($args['extra']);
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
            $sessionVars['resultCount'] = self::NEXT_RESULT_COUNT;
        } else {
            $resultCount                = $sessionVars['resultCount'];
            $sessionVars['resultCount'] = $resultCount + self::NEXT_RESULT_COUNT;
        }
        $GLOBALS['BE_USER']->setAndSaveSessionData('tx_dpf', $sessionVars);

        $query = $sessionVars['query'];

        unset($query['extra']);

        $type = 'object';

        $query['body']['from'] = $sessionVars['resultCount'];
        $query['body']['size'] = self::NEXT_RESULT_COUNT;

        $results = $this->getResultList($query, $type);

        $this->view->assign('resultList', $results);
        $this->view->assign('alreadyImported', array());
    }

    /**
     * extended search action
     */
    public function extendedSearchAction()
    {
        // show extended search template
        $objectIdentifiers = $this->documentRepository->getObjectIdentifiers();

        $args          = $this->request->getArguments();
        $elasticSearch = new \EWW\Dpf\Services\ElasticSearch();
        // assign result list from elastic search
        $this->view->assign('searchList', $args['results']);
        $this->view->assign('alreadyImported', $objectIdentifiers);

        // assign form values
        $this->assignExtraFields($args['extra']);

    }

    /**
     * gets a list of latest documents
     */
    public function latestAction()
    {
        $elasticSearch = new \EWW\Dpf\Services\ElasticSearch();

        $query = $this->searchLatest();

        // set type local vs object
        $type = 'object';

        // unset extra information
        unset($query['extra']);

        $results = $this->getResultList($query, $type);

        $this->forward("list", null, null, array('results' => $results));

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
        $sessionVars                = $GLOBALS['BE_USER']->getSessionData('tx_dpf');
        $sessionVars['resultCount'] = self::RESULT_COUNT;
        $GLOBALS['BE_USER']->setAndSaveSessionData('tx_dpf', $sessionVars);

        // set sorting
        if ($args['extSearch']) {
            // extended search
            $query = $this->extendedSearch();
        } else {
            $query = $this->searchFulltext($args['search']['query']);
        }

        // save search query
        if ($query) {
            $query['body']['from'] = '0';
            $query['body']['size'] = '' . self::RESULT_COUNT . '';
            $sessionVars           = $GLOBALS["BE_USER"]->getSessionData("tx_dpf");
            $sessionVars['query']  = $query;
            $GLOBALS['BE_USER']->setAndSaveSessionData('tx_dpf', $sessionVars);
        } else {
            $sessionVars = $GLOBALS['BE_USER']->getSessionData('tx_dpf');
            $query       = $sessionVars['query'];
        }

        // set type local vs object
        $type = 'object';

        // unset extra information
        $extra = $query['extra'];
        unset($query['extra']);

        $results = $this->getResultList($query, $type);

        if ($args['extSearch']) {
            // redirect to extended search view
            $this->forward("extendedSearch", null, null, array('results' => $results, 'extra' => $extra));
        } else {
            // redirect to list view
            $this->forward("list", null, null, array('results' => $results, 'extra' => $extra));
        }
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
        $remoteRepository        = $this->objectManager->get('\EWW\Dpf\Services\Transfer\FedoraRepository');
        $documentTransferManager->setRemoteRepository($remoteRepository);

        $args[] = $documentObjectIdentifier;

        if ($documentTransferManager->retrieve($documentObjectIdentifier)) {
            $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_retrieve.success';
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
        } else {
            $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_retrieve.failure';
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

        $this->forward('updateIndex', null, null, array('documentObjectIdentifier' => $documentObjectIdentifier));
    }

    /**
     *
     * @param  string $documentObjectIdentifier
     * @return void
     */
    public function updateIndexAction($documentObjectIdentifier)
    {
        $document = $this->documentRepository->findByObjectIdentifier($documentObjectIdentifier);

        if (is_a($document, '\EWW\Dpf\Domain\Model\Document')) {
            $elasticsearchRepository = $this->objectManager->get('\EWW\Dpf\Services\Transfer\ElasticsearchRepository');
            $elasticsearchMapper     = $this->objectManager->get('EWW\Dpf\Helper\ElasticsearchMapper');
            $json                    = $elasticsearchMapper->getElasticsearchJson($document);
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

        // set owner id
        $query['body']['query']['bool']['must'][]['term']['OWNER_ID'] = $client->getOwnerId();

        $results = $elasticSearch->search($query, '');

        $objectIdentifiers = $this->documentRepository->getObjectIdentifiers();

        $this->view->assign('document', $document);
        $this->view->assign('searchList', $results['hits']);
        $this->view->assign('alreadyImported', $objectIdentifiers);

    }

    /**
     * returns the query to get latest documents
     * @return mixed
     */
    public function searchLatest()
    {
        $client = $this->clientRepository->findAll()->current();

        // get the latest documents /CREATED_DATE
        $query['body']['sort'] = array('CREATED_DATE' => array('order' => 'desc'));

        // add owner id
        $query['body']['query']['bool']['must']['term']['OWNER_ID'] = $client->getOwnerId(); // qucosa

        $query['body']['query']['bool']['should'][0]['query_string']['query']                       = '*';
        $query['body']['query']['bool']['should'][1]['has_child']['query']['query_string']['query'] = '*';

        $query['body']['query']['bool']['minimum_should_match'] = "1"; // 1

        $query['body']['query']['bool']['should'][1]['has_child']['child_type'] = "datastream"; // 1

        return $query;
    }

}
