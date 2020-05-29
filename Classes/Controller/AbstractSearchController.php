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
use EWW\Dpf\Services\ElasticSearch\ElasticSearch;

/**
 * Class AbstractSearchController
 * @package EWW\Dpf\Controller
 * @deprecated since version 4.0
 */
abstract class AbstractSearchController extends \EWW\Dpf\Controller\AbstractController
{
    // search terms
    private static $terms   = ['_id', 'OWNER_ID', 'submitter', 'project'];

    // search matches
    private static $matches = ['title', 'abstract', 'author', 'language', 'tag', 'corporation', 'doctype', 'collections'];


    /**
     * get results from elastic search
     * @param  array $query elasticsearch search query
     * @return array        results
     */
    public function getResultList($query, $type)
    {

        $elasticSearch = $this->objectManager->get(ElasticSearch::class);
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
        //$query['body']['query']['bool']['should'][1]['has_child']['query']['query_string']['query'] = $searchString;
        $query['body']['query']['bool']['minimum_should_match'] = "1"; // 1

        // child_type is invalid in elasticsearch 7.5
        //$query['body']['query']['bool']['should'][1]['has_child']['type'] = "datastream"; // 1

        $query = $this->resultsFilter($query, false);

        return $query;

    }

    /**
     * build array for elasticsearch
     * @return array Elasticsearch query array
     */
    public function extendedSearch($searchArray = array())
    {

        $query  = array();
        $filter = array();
        foreach ($searchArray as $key => $qry) {
            $qry = trim($qry);

            if (!empty($qry) && in_array($key, self::$matches)) {

                $query['body']['query']['bool']['must'][] = array('match' => array($key => $qry));

            } elseif (!empty($qry) && in_array($key, self::$terms)) {

                $query['body']['query']['bool']['must'][] = array('term' => array($key => $qry));

            } elseif (!empty($qry) && $key == 'from') {

                if ($dateTime = $this->convertFormDate($qry, false)) {
                    $filter['gte'] = $dateTime->format('Y-m-d');
                }

            } elseif (!empty($qry) && $key == 'till') {

                if ($dateTime = $this->convertFormDate($qry, true)) {
                    $filter['lte'] = $dateTime->format('Y-m-d');
                }

            }
        }

        if (isset($filter['gte']) || isset($filter['lte'])) {

            $query['body']['query']['bool']['must'][] = array('range' => array('distribution_date' => $filter));

        }

        $showDeleted = ($searchArray['showDeleted'] == 'true') ? true : false;
        $query = $this->resultsFilter($query, $showDeleted);
        return $query;

    }

    /**
     * build array for elasticsearch resultfilter
     * @param array Elasticsearch query array
     * @return array Elasticsearch queryFilter array
     */
    public function resultsFilter($query, $showDeleted = false)
    {

        $queryFilter = array();

        // Frontend only
        $searchResultsFilter = $this->settings['searchResultsFilter'];
        if(!empty($searchResultsFilter)) {

            // add doctypes
            if($searchResultsFilter['doctype']) {

                $uids = GeneralUtility::trimExplode(',', $searchResultsFilter['doctype']);
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

                $queryFilter['body']['query']['bool']['must'][] = array('range' => array('distribution_date' => $dateFilter));

            }

            foreach ($searchResultsFilter as $key => $qry) {

                if(!empty($qry)) {
                    $queryFilter['body']['query']['bool']['must'][] = array('match' => array($key => $qry));
                }

            }

        }

        // document must be active
        if($showDeleted == false) {

            //  $queryFilter['body']['query']['bool']['must'][]['term']['STATE'] = 'A';

        };

        // add OWNER_ID if present
        $clients = $this->clientRepository->findAll();
        if ($clients) {
            $client = $clients->getFirst();
            if ($client) {
                //    $queryFilter['body']['query']['bool']['must'][]['term']['OWNER_ID'] = $client->getOwnerId();
            }
        }

        $queryFilter = array_merge_recursive($queryFilter, $query);
        return $queryFilter;
    }

    /**
     * Convert date from form input into DateTime object.
     *
     * A 4 character string is taken for a year and the returning
     * DateTime object is supplemented with either the 01. Jan or 31. Dec
     * depending on the $intervalEnd parameter. This allows querying time
     * intervals like `2000 to 2003`.
     *
     * @param  string    $dateString  Date literal from form
     * @param  bool      $intervalEnd Fills missing values with the maximum possible date if true
     * @return DateTime               Determined date
     */
    public function convertFormDate($dateString, $intervalEnd = false)
    {
        try {
            if (strlen($dateString) == 4) {
                // assuming year
                $year  = $dateString;
                $month = $intervalEnd ? "12" : "01";
                $day   = $intervalEnd ? "31" : "01";
                return new \DateTime("$year-$month-$day");
            } else {
                return new \DateTime($dateString);
            }
        } catch (\Exception $_) {
            return false;
        }
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

        $string = str_replace("/", "\/", $string);

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
}
