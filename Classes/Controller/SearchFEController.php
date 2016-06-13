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

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * SearchFEController
 */
class SearchFEController extends \EWW\Dpf\Controller\AbstractController
{

    /**
     * prepare fulltext query
     *
     * @param  string $searchString
     *
     * @return array query
     */
    public function searchFulltext($searchString)
    {
        // don't return query if searchString is empty
        if (empty($searchString)) {

            return null;

        }

        $client = $this->clientRepository->findAll()->current();

        $searchString = $this->escapeQuery(trim($searchString));

        // we assume all search parts must exist in the result --> to be discussed
        $searchString = '+' . str_replace(' ', ' +', $searchString);

        // add owner id
        $query['body']['query']['bool']['must']['term']['OWNER_ID'] = $client->getOwnerId(); // qucosa

        $query['body']['query']['bool']['should'][0]['query_string']['query']                       = $searchString;
        $query['body']['query']['bool']['should'][1]['has_child']['query']['query_string']['query'] = $searchString;

        $query['body']['query']['bool']['minimum_should_match'] = "1"; // 1

        $query['body']['query']['bool']['should'][1]['has_child']['child_type'] = "datastream"; // 1


        return $query;

    }

    private function escapeQuery($string)
    {
        $luceneReservedCharacters = preg_quote('+-&|!(){}[]^"~?:\\');
        $string                   = preg_replace_callback(
            '/([' . $luceneReservedCharacters . '])/',
            function ($matches) {
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
        // get searchString
        $args = $this->getParametersSafely('search');

        $searchString = $args['query'];

        if (empty($searchString)) {
            // elasticsearch dsl requires an empty object to match all
            $query['body']['query']['match_all'] = new \stdClass();
        } else {
            $query['body']['query']['match']['_all'] = $searchString;
        }

        return $query;
    }

    /**
     * get results from elastic search
     *
     * @param  array $query elasticsearch search query
     * @return array        results
     */
    public function getResultList($query, $type)
    {

        $elasticSearch = new \EWW\Dpf\Services\ElasticSearch();

        $results = $elasticSearch->search($query, $type);

        return $results;
    }

    /*
     * TBD
     */
    public function extendedSearchAction()
    {

    }

    /**
     * action search
     *
     * @return void
     */
    public function searchAction()
    {
        // get searchString
        $args = $this->getParametersSafely('search');

        $searchString = $args['query'];

        // get only as much records as will be shown
        $searchSize = $this->settings['list']['paginate']['itemsPerPage'];

        // default ist first page
        $currentPage = 1;

        if (!empty($searchString)) {

            $this->setSessionData('tx_dpf_searchString', $searchString);

            $searchFrom = 0;

        } else {

            // get last search
            $searchString = $this->getSessionData('tx_dpf_searchString');

            $pagination = $this->getParametersSafely('@widget_0');

            $currentPage = MathUtility::forceIntegerInRange(($pagination['currentPage']), 1);

            $searchFrom = ($currentPage - 1) * $searchSize;

        }

        // prepare search query
        $query = $this->searchFulltext($searchString);

        // execute search query
        if ($query) {
            $query['body']['from'] = $searchFrom;
            $query['body']['size'] = $searchSize;

            // set type local vs object
            $type = 'object';

            $results = $this->getResultList($query, $type);

        }

        $this->view->assign('results', $results);
        $this->view->assign('searchString', $searchString);
        $this->view->assign('currentPage', $currentPage);

    }

    /**
     * action showSearchForm
     *
     * dummy action for showSearchForm Action used in sidebar
     *
     * @return void
     */
    public function showSearchFormAction()
    {

    }
}
