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
use Elasticsearch\Common\Exceptions\Curl\CouldNotConnectToHost;
use Elasticsearch\Common\Exceptions\Curl\CouldNotResolveHostException;
use EWW\Dpf\Domain\Repository\FrontendUserRepository;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Exceptions\ElasticSearchConnectionErrorException;
use EWW\Dpf\Exceptions\ElasticSearchMissingIndexNameException;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use EWW\Dpf\Configuration\ClientConfigurationManager;
use EWW\Dpf\Domain\Model\Document;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class ElasticSearch
{
    /**
     * @var \EWW\Dpf\Configuration\ClientConfigurationManager
     */
    protected $clientConfigurationManager;

    /**
     * @var \Elasticsearch\Client
     */
    protected $client;

    protected $server = 'host.docker.internal'; //127.0.0.1';

    protected $port = '9200';

    protected $indexName = 'kitodo_publication';

    protected $results;


    protected $elasticsearchMapper;

    /**
     * @var int
     */
    protected $clientPid = 0;

    /**
     * elasticsearch client constructor
     * @param int|null $clientPid
     * @throws ElasticSearchMissingIndexNameException
     */
    public function __construct($clientPid = null)
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $this->elasticsearchMapper = $objectManager->get(ElasticsearchMapper::class);

        $this->clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);

        if ($clientPid) {
            $this->clientConfigurationManager->setConfigurationPid($clientPid);
            $this->clientPid = $clientPid;
        }

        $this->server = $this->clientConfigurationManager->getElasticSearchHost();
        $this->port = $this->clientConfigurationManager->getElasticSearchPort();
        $this->indexName = $this->clientConfigurationManager->getElasticSearchIndexName();

        if (empty($this->indexName)) {
            throw new ElasticSearchMissingIndexNameException('Missing search index name.');
        }

        $hosts = array(
            $this->server . ':' . $this->port,
        );

        $clientBuilder = ClientBuilder::create();
        $clientBuilder->setHosts($hosts);
        $this->client = $clientBuilder->build();

        try {
            $this->initializeIndex($this->indexName);
        } catch (\Throwable $e) {
            $message = LocalizationUtility::translate(
                'elasticsearch.notRunning', 'dpf'
            );
            die($message);
        }
    }

    /**
     * @return string|null
     */
    protected function getIndexName()
    {
        return $this->indexName;
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
                        'persons' => [
                            'type' => 'keyword'
                        ],
                        'personsSort' => [
                            'type' => 'text',
                            'fields' => [
                                'keyword' => [
                                    'type' => 'keyword',
                                    'normalizer' => 'lowercase_normalizer'
                                ]
                            ]
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
                        ],
                        'fobIdentifiers' => [
                            'type' => 'keyword'
                        ],
                        'personData' => [
                            //'enabled' => false,
                            'properties' => [
                                'name' => [
                                    'type' => 'keyword'
                                ],
                                'fobId' => [
                                    //'type' => 'keyword'
                                    'enabled' => false
                                ],
                                'index' => [
                                    //'type' => 'integer'
                                    'enabled' => false
                                ]
                            ]
                        ],
                        'affiliation' => [
                            'type' => 'keyword'
                        ],
                        'process_number' => [
                            'type' => 'keyword'
                        ],
                        'creationDate' => [
                            'type' =>  'date',
                            'format'=>  "yyyy-MM-dd"
                        ],
                        'embargoDate' => [
                            'type' =>  'date',
                            'format'=>  "yyyy-MM-dd"
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
        // Fixme: The solution via json_decode and the XSLT file needs to be replaced.
        $data = json_decode($this->elasticsearchMapper->getElasticsearchJson($document));

        if (!$data) {
            $data = new \stdClass();
            $data->title[] = $document->getTitle();
            $data->doctype = $document->getDocumentType()->getName();
        }

        if (is_array($data->distribution_date) && empty($data->distribution_date[0])) {
            $data->distribution_date = null;
        }

        if ($data) {

            $data->state = $document->getState();
            $data->aliasState = DocumentWorkflow::STATE_TO_ALIASSTATE_MAPPING[$document->getState()];

            $data->objectIdentifier = $document->getObjectIdentifier();

            if (!$data->identifier || !is_array($data->identifier)) {
                $data->identifier = [];
            }
            $data->identifier[] = $document->getObjectIdentifier();
            $data->identifier[] = $document->getProcessNumber();

            if ($document->getCreator()) {
                $data->creator = $document->getCreator();
            } else {
                $data->creator = null;
            }

            if ($document->getCreator()) {
                $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
                $frontendUserRepository = $objectManager->get(FrontendUserRepository::class);

                /** @var \EWW\Dpf\Domain\Model\FrontendUser $creatorFeUser */
                $creatorFeUser = $frontendUserRepository->findByUid($document->getCreator());
                if ($creatorFeUser) {
                    $data->creatorRole = $creatorFeUser->getUserRole();
                } else {
                    $data->creatorRole = '';
                }
            } else {
                $data->creatorRole = '';
            }

            $creationDate = new \DateTime($document->getCreationDate());

            $data->creationDate = $creationDate->format('Y-m-d');

            $data->year = $document->getPublicationYear();

            $notes = $document->getNotes();

            if ($notes && is_array($notes)) {
                $data->notes = $notes;
            } else {
                $data->notes = array();
            }


            if ($document->hasFiles()) {
                $data->hasFiles = true;
            } else {
                $data->hasFiles = false;
            }

            $internalFormat = new \EWW\Dpf\Helper\InternalFormat($document->getXmlData(), $this->clientPid);

            //$persons = array_merge($internalFormat->getAuthors(), $internalFormat->getPublishers());
            $persons = $internalFormat->getPersons();

            $fobIdentifiers = [];
            $personData = [];
            foreach ($persons as $person) {
                $fobIdentifiers[] = $person['fobId'];
                $personData[] = $person;
                //$data->persons[] = $person['name'];
                $data->persons[] = $person['fobId'];

                foreach ($person['affiliations'] as $affiliation) {
                    $data->affiliation[] = $affiliation;
                }

                foreach ($person['affiliationIdentifiers'] as $affiliationIdentifier) {
                    $data->affiliation[] = $affiliationIdentifier;
                }
            }

            $data->fobIdentifiers = $fobIdentifiers;
            $data->personData = $personData;

            if (sizeof($persons) > 0) {
                if (array_key_exists('family', $persons[0])) {
                    $data->personsSort = $persons[0]['family'];
                }
            }

            $data->source = $document->getSourceDetails();

            $data->universityCollection = false;
            if ($data->collections && is_array($data->collections)) {
                foreach ($data->collections as $collection) {
                    if ($collection == $this->clientConfigurationManager->getUniversityCollection()) {
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

            $data->originalSourceTitle = $internalFormat->getOriginalSourceTitle();

            $data->fobIdentifiers = $internalFormat->getPersonFisIdentifiers();

            $this->client->index([
                'refresh' => 'wait_for',
                'index' => $this->getIndexName(),
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
                'index' => $this->getIndexName(),
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
            'index' => $this->getIndexName(),
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
                $query['index'] = $this->getIndexName();
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
        } catch (CouldNotConnectToHost $exception) {
            throw new ElasticSearchConnectionErrorException("Could not connect to repository server.");
        } catch (CouldNotResolveHostException $exception) {
            throw new ElasticSearchConnectionErrorException("Could not connect to repository server.");
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
