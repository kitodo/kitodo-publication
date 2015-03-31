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
     * action search
     * @return void
     */
    public function searchAction()
    {
        // perform search action
        $args = $this->request->getArguments();

        $elasticSearch = new \EWW\Dpf\Services\ElasticSearch();

        if ($args['extSearch']) {
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

            if ($args['extSearch']['extBla']) {
                $bla = $title = $args['extSearch']['extTitle'];
                $fieldQuery['testField'] = 'abc';
                $countFields++;
            }

            if ($countFields > 1) {
                // put query together for multi field search
                $i = 0;
                foreach ($fieldQuery as $key => $qry) {
                    $query['body']['query']['bool']['must'][$i]['match'][$key] = $qry;
                    $i++;
                }
            } else {
                // use single query
                $query['body']['query']['match'] = $fieldQuery;
            }
            

        } else {
            if (empty($args['search']['query'])) {
                // elasticsearch dsl requires an empty object to match all
                $query['body']['query']['match_all'] = new \stdClass();
            } else {
                $query['body']['query']['match']['_all'] = $args['search']['query'];
            }
            
        }

        // save search query
        if ($query) {
            $sessionVars = $GLOBALS["BE_USER"]->getSessionData("tx_dpf");
            $sessionVars['query'] = $query;
            $GLOBALS['BE_USER']->setAndSaveSessionData('tx_dpf', $sessionVars);
        } else {
            $sessionVars = $GLOBALS['BE_USER']->getSessionData('tx_dpf');
            $query = $sessionVars['query'];
        }

        $results = $elasticSearch->search($query);

        // redirect to list view
        $this->forward("list", null, null, array('results' => $results));
    }

    /**
     * action import
     *
     * @param string $documentObjectIdentifier
     * @return void
     */
    public function importAction($documentObjectIdentifier)
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

        $this->redirect('search');
    }
}
