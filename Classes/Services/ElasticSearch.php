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

use EWW\Dpf\Exceptions\RepositoryConnectionErrorException;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use EWW\Dpf\Configuration\ClientConfigurationManager;

/**
 * ElasticSearch
 */
class ElasticSearch
{
    protected $baseUrl = '';

    protected $index = 'fedora';

    protected $hits;

    protected $resultList;

    public function __construct()
    {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);
        $clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);

        $host = $clientConfigurationManager->getElasticSearchHost();
        $port = $clientConfigurationManager->getElasticSearchPort();

        $this->baseUrl = 'http://' . $host . ':' . $port;
    }

    public function search($query, $type)
    {
        $index = !empty($query['index']) ? $query['index'] : $this->index;
        $body  = isset($query['body']) ? $query['body'] : new \stdClass();
        $url   = $this->baseUrl . '/' . $index . '/_search';

        try {
            $response = \Httpful\Request::post($url)
                ->sendsJson()
                ->body(json_encode($body))
                ->send();
        } catch (\Exception $e) {
            throw new \EWW\Dpf\Exceptions\ElasticSearchConnectionErrorException("Could not connect to repository server.");
        }

        if ($response->code === 0) {
            throw new \EWW\Dpf\Exceptions\ElasticSearchConnectionErrorException("Could not connect to repository server.");
        }

        $results = json_decode($response->raw_body, true);

        $this->hits       = $results['hits']['total']['value'];
        $this->resultList = $results['hits'];

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
