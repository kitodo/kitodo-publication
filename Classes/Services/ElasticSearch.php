<?php
namespace EWW\Dpf\Services;

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
use \ElasticSearch\Client;
/**
 * ElasticSearch
 */
class ElasticSearch
{
    protected $es;

    protected $server = ''; //127.0.0.1';

    protected $port = '9200';

    protected $index = 'fedora';

    protected $type = 'object';

    protected $hits;

    protected $resultList;

    
    /**
     * elasticsearch client constructor
     */
    public function __construct() {
        
        $confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['dpf']);
        $this->server = $confArr['elasticSearchHost'];
        $this->port = $confArr['elasticSearchPort'];
     

        // initialize elasticsearch lib
        $extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dpf');
        require_once($extensionPath . '/Lib/ElasticSearchPhpClient/vendor/autoload.php');
        
        // establish connection 
        $this->es = Client::connection(array(
            'servers' => $this->server.':'.$this->port,
            'protocol' => 'http',
            'index' => $this->index,
            'type' => $this->type
        )); 

        $query = 'Qucosa';

        $this->search($query);

        // $results = $this->es->search((string) $query);

        // print_r($results);
                
    }

    /**
     * performs the 
     * @param  string $query search query
     * @return array        result list
     */
    public function search($query = '')
    {
        // Search request
        $results = $this->es->search((string) $query);

        $this->hits = $results['hits']['total'];
        // print_r($this->hits);

        $this->resultList = $results['hits']['hits'];
        // print_r($results['hits']['hits'][0]);
        // print_r($results['hits']['hits'][1]);

        return $this->resultList;
    }

    /**
     * returns the result list
     * @return [type] [description]
     */
    public function getResults()
    {
        // return results from the last search request
        return $this->resultList;
    }


   
}
