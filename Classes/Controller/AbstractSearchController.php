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

use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractSearchController extends \EWW\Dpf\Controller\AbstractController
{

    /**
     * get results from elastic search
     * @param  array $query elasticsearch search query
     * @return array        results
     */
    public function getResultList($query, $type)
    {
        $elasticSearch = new \EWW\Dpf\Services\ElasticSearch();
        $results = $elasticSearch->search($query, $type);

        return $results;
    }

    /**
     * prepare fulltext query
     * @param  string $searchString
     * @return array query
     */
    public function searchFulltext($searchString)
    {
        // don't return query if searchString is empty
        if (empty($searchString)) {
            return null;
        }

        $searchString = $this->escapeQuery(trim($searchString));

        $query['body']['query']['bool']['should'][0]['query_string']['query']                       = $searchString;
        $query['body']['query']['bool']['should'][1]['has_child']['query']['query_string']['query'] = $searchString;
        $query['body']['query']['bool']['minimum_should_match'] = "1"; // 1
        $query['body']['query']['bool']['should'][1]['has_child']['child_type'] = "datastream"; // 1

        $query = $this->resultsFilter($query);

        return $query;

    }

    /**
     * build array for elasticsearch
     * @return array Elasticsearch query array
     */
    public function extendedSearch()
    {
        $args   = $this->request->getArguments();

        // extended search
        $countFields = 0;

        if ($args['extSearch']['extId']) {

            $id                = $args['extSearch']['extId'];
            $fieldQuery['_id'] = $id;
            $countFields++;
            // will be removed from query later
            $query['extra']['id'] = $id;

        }

        if ($args['extSearch']['extTitle']) {

            $title               = $args['extSearch']['extTitle'];
            $fieldQuery['_dissemination._content.title'] = $title;
            $countFields++;
            // will be removed from query later
            $query['extra']['title'] = $title;

        }

        if ($args['extSearch']['extAuthor']) {

            $author               = $args['extSearch']['extAuthor'];
            $fieldQuery['author'] = $author;
            $countFields++;
            // will be removed from query later
            $query['extra']['author'] = $author;

        }

        if ($args['extSearch']['extType']) {

            $docType               = $args['extSearch']['extType'];
            $fieldQuery['doctype'] = $docType;
            $countFields++;
            // will be removed from query later
            $query['extra']['doctype'] = $docType;

        }

        if ($args['extSearch']['extInstitution']) {

            $corporation                = $args['extSearch']['extInstitution'];
            $fieldQuery['corporation']  = $corporation;
            $countFields++;
            // will be removed from query later
            $query['extra']['corporation']  = $corporation;

        }

        if ($args['extSearch']['extTag']) {

            $tag               = $args['extSearch']['extTag'];
            $fieldQuery['tag'] = $tag;
            $countFields++;
            // will be removed from query later
            $query['extra']['tag'] = $tag;

        }

        if ($args['extSearch']['extDeleted']) {

            // STATE deleted
            $delete['bool']['must'][] = array('match' => array('STATE' => 'D'));

            // STATE inactive
            $inactive['bool']['must'][] = array('match' => array('STATE' => 'I'));
            $query['body']['query']['bool']['should'][] = $delete;
            $query['body']['query']['bool']['should'][] = $inactive;
            $query['body']['query']['bool']['minimum_should_match'] = 1;

            $query['extra']['showDeleted'] = true;

        } else {

            // STATE active
            $deleted             = true;
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

        // filter
        $filter = array();
        if ($args['extSearch']['extFrom']) {

            $from          = $args['extSearch']['extFrom'];

            $dateTime = $this->convertFormDate($from, false);

            //$filter['gte'] = $this->formatDate($from);
            $filter['gte'] = $dateTime->format('Y-m-d');

            // saves data for form (will be removed from query later)
            $query['extra']['from'] = $dateTime->format('d.m.Y');

        }

        if ($args['extSearch']['extTill']) {

            $till          = $args['extSearch']['extTill'];
            $filter['lte'] = $this->formatDate($till);

            $dateTime = $this->convertFormDate($till, true);

            $filter['lte'] = $dateTime->format('Y-m-d');

            // saves data for form (will be removed from query later)
            $query['extra']['till'] = $dateTime->format('d.m.Y');

        }

        if (isset($filter['gte']) || isset($filter['lte'])) {

            $query['body']['query']['bool']['must'][] = array('range' => array('distribution_date' => $filter));

        }

        $query = $this->resultsFilter($query);
        return $query;
    }

    /**
     * build array for elasticsearch resultfilter
     * @param array Elasticsearch query array
     * @return array Elasticsearch queryFilter array
     */
    public function resultsFilter($query)
    {
        $queryFilter = array();
        $searchResultsFilter = $this->settings['searchResultsFilter'];

        // add doctypes
        if($searchResultsFilter['doctype']) {

            $uids = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $searchResultsFilter['doctype']);
            $documentTypeRepository = $this->documentTypeRepository;
            $documentTypes = array();
            foreach($uids as $uid) {
                $documentType = $documentTypeRepository->findByUid($uid);
                $documentTypes[] = $documentType->getName();
            };
            $searchResultsFilter['doctype'] = implode(',', $documentTypes);
        }

        // add date filter
        $dateFilter = array();
        if ($searchResultsFilter['from']) {

            $from     = date('d.m.Y', $searchResultsFilter['from']);
            $dateTime = $this->convertFormDate($from, false);
            $dateFilter['gte'] = $dateTime->format('Y-m-d');
            unset($searchResultsFilter['from']);

        }

        if ($searchResultsFilter['till']) {

            $till          = date('d.m.Y', $searchResultsFilter['till']);
            $dateTime = $this->convertFormDate($till, true);
            $dateFilter['lte'] = $dateTime->format('Y-m-d');
            unset($searchResultsFilter['till']);

        }

        if (isset($dateFilter['gte']) || isset($dateFilter['lte'])) {

            $query['body']['query']['bool']['must'][] = array('range' => array('distribution_date' => $dateFilter));

        }

        // add owner id
        $client = $this->clientRepository->findAll()->current();
        $searchResultsFilter['OWNER_ID'] = $client->getOwnerId(); // qucosa

        foreach ($searchResultsFilter as $key => $qry) {

            if(!empty($qry)) {
                $queryFilter['body']['query']['bool']['must'][] = array('match' => array($key => $qry));
            }

        }

        $queryFilter = array_merge_recursive($queryFilter, $query);

        return $queryFilter;
    }

    /**
     * @param $date
     * @param bool $fillMax: fills missing values with the maximum possible date if true
     */
    public function convertFormDate($date, $fillMax = false) {

        $dateTime = new \DateTime('01-01-2000');

        $date = explode(".", $date);
        $year = 1;
        if ($fillMax) {
            $month = 12;
        } else {
            $month = 1;
        }
        $day = 1;

        // reverse array to get year first
        foreach (array_reverse($date) as $key => $value) {
            if (strlen($value) == 4) {
                $year = $value;
            } else {
                if ($key == 1) {
                    $month = $value;
                } else if ($key == 2){
                    $day = $value;
                }
            }
        }

        $dateTime->setDate($year, $month, $day);

        if ($fillMax) {
            $maxDayFormMonth = $dateTime->format('t');
            $dateTime->setDate($year, $month, $maxDayFormMonth);
        }

        return $dateTime;
    }

    /**
     * escapes lucene reserved characters from string
     * @param $string
     * @return mixed
     */
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
     * converts a date from dd.mm.yy to yyyy-dd-mm
     * @param $date
     * @return string
     */
    public function formatDate($date)
    {
        // convert date from dd.mm.yyy to yyyy-dd-mm
        $date = explode(".", $date);
        return $date[2] . '-' . $date[1] . '-' . $date[0];
    }

    /**
     * assigns an array to view
     * @param $array
     */
    public function assignExtraFields($array)
    {
        // assign all form(extra) field values
        if (is_array($array)) {

            foreach ($array as $key => $value) {
                $this->view->assign($key, $value);
            }

        }
    }
}
