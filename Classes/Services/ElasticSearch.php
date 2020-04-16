<?php
namespace EWW\Dpf\Services;

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

use Elasticsearch\Client as Client;
use EWW\Dpf\Exceptions\RepositoryConnectionErrorException;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use EWW\Dpf\Configuration\ClientConfigurationManager;

/**
 * ElasticSearch
 */
class ElasticSearch
{
    // obsolete class

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
    public function __construct()
    {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);

        $this->server = $clientConfigurationManager->getElasticSearchHost();
        $this->port   = $clientConfigurationManager->getElasticSearchPort();

        // initialize elasticsearch lib
        $extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dpf');

        $params['hosts'] = array(
            $this->server . ':' . $this->port,
        );

        // $client = ClientBuilder::create()->build();
        $this->es = new Client($params);

    }

    /**
     * performs the
     * @param  array $query search query
     * @return array        result list
     */
    public function search($query, $type)
    {
        try {
            // define type and index
            if (empty($query['index'])) {
                $query['index'] = $this->index;
            }
            if (!empty($type)) {
                $query['type'] = $type;
                // $query['type'] = $this->type;
            }

            // Search request
            $results = $this->es->search($query);

            $this->hits = $results['hits']['total'];

            $this->resultList = $results['hits'];

            return $this->resultList;
        } catch ( \Elasticsearch\Common\Exceptions\Curl\CouldNotConnectToHost $exception) {
            throw new \EWW\Dpf\Exceptions\ElasticSearchConnectionErrorException("Could not connect to repository server.");
        } catch (\Elasticsearch\Common\Exceptions\Curl\CouldNotResolveHostException $exception) {
            throw new \EWW\Dpf\Exceptions\ElasticSearchConnectionErrorException("Could not connect to repository server.");
        }
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
