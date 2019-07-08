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

use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Services\Transfer\DocumentTransferManager;
use EWW\Dpf\Services\Transfer\FedoraRepository;
use EWW\Dpf\Services\Transfer\ElasticsearchRepository;
use EWW\Dpf\Services\ElasticSearch;
use EWW\Dpf\Helper\ElasticsearchMapper;
use EWW\Dpf\Exceptions\DPFExceptionInterface;

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

        // assign result list from elastic search
        $this->view->assign('searchList', $args['results']);
        $this->view->assign('alreadyImported', $objectIdentifiers);
        $this->view->assign('resultCount', self::RESULT_COUNT);
        $this->view->assign('query', $args['query']);
    }

    /**
     * get next search results
     * @return array ElasticSearch results
     */
    public function nextResultsAction()
    {
        try {
            $sessionVars = $GLOBALS["TSFE"]->getSessionData("tx_dpf");
            if (!$sessionVars['resultCount']) {
                // set number of results in session
                $sessionVars['resultCount'] = self::NEXT_RESULT_COUNT;
            } else {
                $resultCount                = $sessionVars['resultCount'];
                $sessionVars['resultCount'] = $resultCount + self::NEXT_RESULT_COUNT;
            }
            $GLOBALS['TSFE']->setAndSaveSessionData('tx_dpf', $sessionVars);

            $query = $sessionVars['query'];

            $type = 'object';

            $query['body']['from'] = $sessionVars['resultCount'];
            $query['body']['size'] = self::NEXT_RESULT_COUNT;

            $results = $this->getResultList($query, $type);

            $this->view->assign('resultList', $results);
            $this->view->assign('alreadyImported', array());
        } catch (\Exception $exception) {
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }

            $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf');

            $this->addFlashMessage(
                $message,
                '',
                $severity,
                true
            );
        }

    }

    /**
     * extended search action
     */
    public function extendedSearchAction()
    {
        // show extended search template
        $objectIdentifiers = $this->documentRepository->getObjectIdentifiers();

        $args          = $this->request->getArguments();

        // assign result list from elastic search
        $this->view->assign('searchList', $args['results']);
        $this->view->assign('alreadyImported', $objectIdentifiers);
        $this->view->assign('resultCount', self::RESULT_COUNT);

        $this->view->assign('query', $args['query']);

    }

    /**
     * gets a list of latest documents
     */
    public function latestAction()
    {
        try {
            $query = $this->searchLatest();

            // set type local vs object
            $type = 'object';

            $results = $this->getResultList($query, $type);
        } catch (\Exception $exception) {
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }

            $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf');

            $this->addFlashMessage(
                $message,
                '',
                $severity,
                true
            );

        }

        $this->forward("list", null, null, array('results' => $results));
    }

    /**
     * action search
     * @return void
     */
    public function searchAction()
    {
        try {
            // perform search action
            $args = $this->request->getArguments();

            // reset session pagination
            $sessionVars = $this->getSessionData('tx_dpf');
            $sessionVars['resultCount'] = self::RESULT_COUNT;
            $this->setSessionData('tx_dpf', $sessionVars);

            $extSearch = ($args['query']['extSearch']) ? true : false;

            // set sorting
            if ($extSearch) {
                unset($args['query']['extSearch']);
                // extended search
                $query = $this->extendedSearch($args['query']);

            } else {
                $query = $this->searchFulltext($args['query']['fulltext']);
            }

            // save search query
            if ($query) {
                $query['body']['from'] = '0';
                $query['body']['size'] = '' . self::RESULT_COUNT . '';
                $sessionVars = $this->getSessionData("tx_dpf");
                $sessionVars['query'] = $query;
                $this->setSessionData('tx_dpf', $sessionVars);
            } else {
                $sessionVars = $this->getSessionData('tx_dpf');
                $query = $sessionVars['query'];
            }

            // set type local vs object
            $type = 'object';

            $results = $this->getResultList($query, $type);
        } catch (\Exception $exception) {
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }

            $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf');

            $this->addFlashMessage(
                $message,
                '',
                $severity,
                true
            );
        }

        if ($extSearch) {
            // redirect to extended search view
            $this->forward("extendedSearch", null, null, array('results' => $results, 'query' => $args['query']));
        } else {
            // redirect to list view
            $this->forward("list", null, null, array('results' => $results, 'query' => $args['query']));
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
        $documentTransferManager = $this->objectManager->get(DocumentTransferManager::class);
        $remoteRepository        = $this->objectManager->get(FedoraRepository::class);
        $documentTransferManager->setRemoteRepository($remoteRepository);

        $args = array();

        try {
            if ($documentTransferManager->retrieve($documentObjectIdentifier)) {
                $key      = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:document_retrieve.success';
                $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::OK;
                $document = $this->documentRepository->findOneByObjectIdentifier($documentObjectIdentifier);
                $args[] = $document->getObjectIdentifier()." (".$document->getTitle().")";
            }
        } catch (\Exception $exception) {
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }
        }

        // Show success or failure of the action in a flash message

        $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf', $args);

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

        if (is_a($document, Document::class)) {
            $elasticsearchRepository = $this->objectManager->get(ElasticsearchRepository::class);
            $elasticsearchMapper     = $this->objectManager->get(ElasticsearchMapper::class);
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
        try {
            $elasticSearch = $this->objectManager->get(ElasticSearch::class);

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

            $searchList = array();

            // filter out identical document from the search result list
            foreach ($results['hits'] as $entry) {

                if ($document->getObjectIdentifier() && ($document->getObjectIdentifier() === $entry['_source']['PID'])) {
                    continue;
                }

                $entryIdentifier = $entry['_source']['_dissemination']['_content']['identifier'][0];
                if (is_numeric($entryIdentifier) && $document->getUid() == $entryIdentifier) {
                    continue;
                }

                $searchList[] = $entry;
            }


            $objectIdentifiers = $this->documentRepository->getObjectIdentifiers();

            $this->view->assign('document', $document);
            $this->view->assign('searchList', $searchList);
            $this->view->assign('alreadyImported', $objectIdentifiers);

        } catch (\Exception $exception) {
            $severity = \TYPO3\CMS\Core\Messaging\AbstractMessage::ERROR;

            if ($exception instanceof DPFExceptionInterface) {
                $key = $exception->messageLanguageKey();
            } else {
                $key = 'LLL:EXT:dpf/Resources/Private/Language/locallang.xlf:error.unexpected';
            }

            $message = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, 'dpf');

            $this->addFlashMessage(
                $message,
                '',
                $severity,
                true
            );

            $this->redirect('list', 'Document', null);
        }

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
