<?php
namespace EWW\Dpf\Services\ElasticSearch;

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

use Elasticsearch\ClientBuilder;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use EWW\Dpf\Configuration\ClientConfigurationManager;
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Helper\Mods;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Log\LogManager;

class ElasticSearch
{
    /**
     *
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     * @inject
     */
    protected $configurationManager;

    /**
     * frontendUserRepository
     *
     * @var \EWW\Dpf\Domain\Repository\FrontendUserRepository
     * @inject
     */
    protected $frontendUserRepository = null;

    protected $client;

    protected $server = 'host.docker.internal'; //127.0.0.1';

    protected $port = '9200';

    protected $indexName = 'kitodo_publication';

    //protected $mapping = '';

    //protected $hits;

    protected $results;

    protected $elasticsearchMapper;


    /**
     * elasticsearch client constructor
     */
    public function __construct()
    {
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ObjectManager::class);

        $this->elasticsearchMapper = $objectManager->get(ElasticsearchMapper::class);

        $clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);

        $this->server = $clientConfigurationManager->getElasticSearchHost();
        $this->port = $clientConfigurationManager->getElasticSearchPort();

        $hosts = array(
            $this->server . ':' . $this->port,
        );

        $clientBuilder = ClientBuilder::create();
        $clientBuilder->setHosts($hosts);
        $this->client = $clientBuilder->build();

        $this->initializeIndex($this->indexName);

    }

    /**
     * Get typoscript settings
     *
     * @return mixed
     */
    public function getSettings()
    {
        $frameworkConfiguration = $this->configurationManager->getConfiguration(
            \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK
        );
        return $frameworkConfiguration['settings'];
    }


    /**
     * Creates an index named by $indexName if it doesn't exist.
     *
     * @param $indexName
     */
    protected function initializeIndex($indexName)
    {
        $paramsIndex = [
            'index' => $indexName,
            'body' => [
                'settings' => [
                    //'index.requests.cache.enable' => false,
                    'analysis' => [
                        'filter' => [
                            'ngram' => [
                                'type' => 'ngram',
                                'min_gram' => 3,
                                'max_gram' => 3,
                                'token_chars' => [
                                    'letter',
                                    'digit'
                                ],
                            ]
                        ],
                        'analyzer' => [
                            'keyword_lowercase' => [
                                'tokenizer' => 'keyword',
                                'filter' => ['lowercase']
                            ]
                        ],
                        'normalizer' => [
                            'lowercase_normalizer' => [
                                'type' => 'custom',
                                'char_filter' => [],
                                'filter' => [
                                    'lowercase',
                                    'asciifolding'
                                ]
                            ]
                        ]
                    ]
                ],
                'mappings' => [
                    '_source' => [
                        'enabled' => true
                    ],
                    //'dynamic' => 'strict',
                    'properties' => [
                        'title' => [
                            'type' => 'text',
                            'fields' => [
                                'keyword' => [
                                    'type' => 'keyword',
                                    'normalizer' => 'lowercase_normalizer'
                                ]
                            ]
                        ],
                        'state' => [
                            'type' => 'keyword'
                        ],
                        'aliasState' => [
                            'type' => 'keyword'
                        ],
                        'year' => [
                            'type' => 'integer'
                        ],
                        'authorAndPublisher' => [
                            'type' => 'keyword'
                        ],
                        'doctype' => [
                            'type' => 'keyword'
                        ],
                        'collections' => [
                            'type' => 'keyword'
                        ],
                        'hasFiles' => [
                            'type' => 'keyword'
                        ],
                        'creator' => [
                            'type' => 'keyword'
                        ],
                        'creatorRole' => [
                            'type' => 'keyword'
                        ],
                        'source' => [
                            'type' => 'text'
                        ]
                    ]
                ]
            ]
        ];

        if (!$this->client->indices()->exists(['index' => $indexName])) {
            $this->client->indices()->create($paramsIndex);
        }

    }

    /**
     * Adds an document to the index.
     *
     * @param Document $document
     */
    public function index($document)
    {
        $data = json_decode($this->elasticsearchMapper->getElasticsearchJson($document));

        if ($data) {

            $data->state = $document->getState();
            $data->aliasState = DocumentWorkflow::STATE_TO_ALIASSTATE_MAPPING[$document->getState()];
            $data->objectIdentifier = $document->getObjectIdentifier();


            if ($data->identifier && is_array($data->identifier)) {
                $data->identifier[] = $document->getObjectIdentifier();
            } else {
                $data->identifier = [$document->getObjectIdentifier()];
            }


            if ($document->getCreator()) {
                $data->creator = $document->getCreator();
            } else {
                $data->creator = null;
            }


            if ($document->getCreator()) {
                /** @var \EWW\Dpf\Domain\Model\FrontendUser $creatorFeUser */
                $creatorFeUser = $this->frontendUserRepository->findByUid($document->getCreator());
                $data->creatorRole = $creatorFeUser->getUserRole();
            } else {
                $data->creatorRole = '';
            }

            $data->year = $document->getPublicationYear();

            $notes = $document->getNotes();

            if ($notes && is_array($notes)) {
                $data->notes = $notes;
            } else {
                $data->notes = array();
            }

            $files = $document->getFile();
            if ($files->count() > 0) {
                $data->hasFiles = true;
            } else {
                $data->hasFiles = false;
            }


            /** @var @var Mods $mods */
            $mods = new Mods($document->getXmlData());

            $authors = $mods->getAuthors();
            $publishers = $mods->getPublishers();

            $data->authorAndPublisher = array_merge($authors, $publishers);

            $data->source = $document->getSourceDetails();


            $data->universityCollection = false;
            if ($data->collections && is_array($data->collections)) {
                foreach ($data->collections as $collection) {
                    if ($collection == $this->getSettings()['universityCollection']) {
                        $data->universityCollection = true;
                        break;
                    }
                }
            }

            $embargoDate = $document->getEmbargoDate();
            if ($embargoDate instanceof \DateTime) {
                $data->embargoDate = $embargoDate->format("Y-m-d");
            } else {
                $data->embargoDate = null;
            }

            $data->originalSourceTitle = $mods->getOriginalSourceTitle();

            $this->client->index([
                'refresh' => 'wait_for',
                'index' => $this->indexName,
                'id' => $document->getDocumentIdentifier(),
                'body' => $data
            ]);

        }

    }


    /**
     * Deletes a document from the index
     *
     * @param string $identifier
     */
    public function delete($identifier)
    {
        try {

            $params = [
                'refresh' => 'wait_for',
                'index' => $this->indexName,
                'id' => $identifier
            ];

            $this->client->delete($params);

        } catch (\Exception $e) {
            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
            $logger->warning('Document could not be deleted from the index.',
                [
                    'Document identifier' => $identifier
                ]
            );
        }
    }


    /**
     * @param $identifier
     */
    public function getDocument($identifier)
    {
        $params = [
            'index' => $this->indexName,
            'id'    => $identifier
        ];

        return $this->client->get($params);
    }


    /**
     * performs the
     * @param  array $query search query
     * @return array        result list
     */
    public function search($query, $type = null)
    {
        try {
            // define type and index
            if (empty($query['index'])) {
                $query['index'] = $this->indexName;
            }
            if (!empty($type)) {
                //$query['type'] = $type;
                // $query['type'] = $this->type;
            }

            // Search request
            $results = $this->client->search($query);

            //$this->hits = $results['hits']['total'];

            //$this->resultList = $results['hits'];

            $this->results = $results;

            return $this->results;
        } catch ( \Elasticsearch\Common\Exceptions\Curl\CouldNotConnectToHost $exception) {
            throw new \EWW\Dpf\Exceptions\ElasticSearchConnectionErrorException("Could not connect to repository server.");
        } catch (\Elasticsearch\Common\Exceptions\Curl\CouldNotResolveHostException $exception) {
            throw new \EWW\Dpf\Exceptions\ElasticSearchConnectionErrorException("Could not connect to repository server.");
        }
    }

    /**
     * Get the results
     * @return mixed
     */
    public function getResults()
    {
        // return results from the last search request
        return $this->results;
    }
}
