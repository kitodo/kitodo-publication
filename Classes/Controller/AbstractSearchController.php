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

        // add owner id
        $query['body']['query']['bool']['must']['term']['OWNER_ID'] = $client->getOwnerId(); // qucosa

        $query['body']['query']['bool']['should'][0]['query_string']['query']                       = $searchString;
        $query['body']['query']['bool']['should'][1]['has_child']['query']['query_string']['query'] = $searchString;

        $query['body']['query']['bool']['minimum_should_match'] = "1"; // 1

        $query['body']['query']['bool']['should'][1]['has_child']['child_type'] = "datastream"; // 1

        return $query;

    }

    public function extractQuotedString($string) {
        if (preg_match('/"([^"]+)"/', $string, $m)) {
            $array['quoted'] = $m[1];
            $array['nonQuoted'] = trim(str_replace($m[0], "", $string));

            return $array;
        } else {
            return false;
        }
    }

    /**
     * build array for elasticsearch
     * @return array Elasticsearch query array
     */
    public function extendedSearch()
    {
        $args   = $this->request->getArguments();
        $client = $this->clientRepository->findAll()->current();

        // extended search
        $countFields = 0;

        if ($args['extSearch']['extId']) {

            $id                = $args['extSearch']['extId'];
            $fieldQuery[]['_id'] = $id;
            $countFields++;

            // saves data for form (will be removed from query later)
            $query['extra']['id'] = $id;

        }

        if ($args['extSearch']['extTitle']) {

            $title = $args['extSearch']['extTitle'];
            $titleQuery = $title;

            if ($extract = $this->extractQuotedString($title)) {

                $title = $extract['nonQuoted'];
                $fieldQuery['quoted']['_dissemination._content.title']['query'] = $extract['quoted'];
                $fieldQuery['quoted']['_dissemination._content.title']['type'] = 'phrase';

            }

            if (!empty($title)) {
                $fieldQuery['nonQuoted']['_dissemination._content.title'] = $title;
            }

            $countFields++;

            // saves data for form (will be removed from query later)
            $query['extra']['title'] = $titleQuery;


        }

        if ($args['extSearch']['extAuthor']) {

            $author               = $args['extSearch']['extAuthor'];
            //$fieldQuery['author'] = $author;

            $authorQuery = $author;

            if ($extract = $this->extractQuotedString($author)) {

                $author = $extract['nonQuoted'];
                $fieldQuery['quoted']['_dissemination._content.author']['query'] = $extract['quoted'];
                $fieldQuery['quoted']['_dissemination._content.author']['type'] = 'phrase';

            }

            if (!empty($author)) {
                $fieldQuery['nonQuoted']['_dissemination._content.author'] = $author;
            }

            $countFields++;
            // saves data for form (will be removed from query later)
            $query['extra']['author'] = $authorQuery;

        }

        if ($args['extSearch']['extType']) {

            $docType               = $args['extSearch']['extType'];
            // $fieldQuery['doctype'] = $docType;

            $docTypeQuery = $docType;

            if ($extract = $this->extractQuotedString($docType)) {

                $docType = $extract['nonQuoted'];
                $fieldQuery['quoted']['_dissemination._content.doctype']['query'] = $extract['quoted'];
                $fieldQuery['quoted']['_dissemination._content.doctype']['type'] = 'phrase';

            }

            if (!empty($docType)) {
                $fieldQuery['nonQuoted']['_dissemination._content.doctype'] = $docType;
            }

            $countFields++;
            // saves data for form (will be removed from query later)
            $query['extra']['doctype'] = $docTypeQuery;

        }


        if ($args['extSearch']['extCorporation']) {

            $corporation                = $args['extSearch']['extCorporation'];
            // $fieldQuery['corporation']  = $corporation;

            if ($extract = $this->extractQuotedString($corporation)) {

                $corporation = $extract['nonQuoted'];
                $fieldQuery['quoted']['_dissemination._content.corporation']['query'] = $extract['quoted'];
                $fieldQuery['quoted']['_dissemination._content.corporation']['type'] = 'phrase';

            }

            if (!empty($corporation)) {
                $fieldQuery['nonQuoted']['_dissemination._content.corporation'] = $corporation;
            }

            $countFields++;
            // saves data for form (will be removed from query later)
            $query['extra']['corporation']  = $corporation;

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
            $fieldQuery[]['STATE'] = 'A';
            $countFields++;

        }

        if ($countFields >= 1) {

            // multi field search
            $i = 1;
            foreach ($fieldQuery as $key => $qry) {
                if (is_array($qry[key($qry)])) {

                    $array['match'][key($qry)] = array(
                        "query" => $qry[key($qry)]['query'],
                        "type" => $qry[key($qry)]['type']
                    );

                    $query['body']['query']['bool']['must'][] = $array;
                } else {

                    $query['body']['query']['bool']['must'][] = array('match' => $qry);
                }

                $i++;
            }

        }

        // filter
        $filter = array();
        if ($args['extSearch']['extFrom']) {

            $from          = $args['extSearch']['extFrom'];
            $filter['gte'] = $this->formatDate($from);
            // saves data for form (will be removed from query later)
            $query['extra']['from'] = $from;

        }

        if ($args['extSearch']['extTill']) {

            $till          = $args['extSearch']['extTill'];
            $filter['lte'] = $this->formatDate($till);
            // saves data for form (will be removed from query later)
            $query['extra']['till'] = $till;

        }

        if (isset($filter['gte']) || isset($filter['lte'])) {

            $query['body']['query']['bool']['must'][] = array('range' => array('CREATED_DATE' => $filter));

        }

        // owner id
        $query['body']['query']['bool']['must'][] = array('match' => array('OWNER_ID' => $client->getOwnerId()));

        return $query;
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
