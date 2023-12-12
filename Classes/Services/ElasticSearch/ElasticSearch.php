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

use DateTime;
use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\Curl\CouldNotConnectToHost;
use Elasticsearch\Common\Exceptions\Curl\CouldNotResolveHostException;
use EWW\Dpf\Configuration\ClientConfigurationManager;
use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Domain\Model\FrontendUser;
use EWW\Dpf\Domain\Repository\FrontendUserRepository;
use EWW\Dpf\Domain\Workflow\DocumentWorkflow;
use EWW\Dpf\Exceptions\ElasticSearchConnectionErrorException;
use EWW\Dpf\Exceptions\ElasticSearchMissingIndexNameException;
use EWW\Dpf\Services\Api\InternalFormat;
use Exception;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class ElasticSearch
{
    /**
     * @var ClientConfigurationManager
     */
    protected $clientConfigurationManager;

    /**
     * @var Client
     */
    protected $client;

    protected $server = 'host.docker.internal'; //127.0.0.1';

    protected $port = '9200';

    protected $indexName = 'kitodo_publication';

    protected $results;


    /**
     * elasticsearch client constructor
     * @throws ElasticSearchMissingIndexNameException
     */
    public function __construct()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $this->clientConfigurationManager = $objectManager->get(ClientConfigurationManager::class);

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
                'elasticsearch.notRunning',
                'dpf'
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
                            'type' => 'text'
                        ],
                        'process_number' => [
                            'type' => 'keyword'
                        ],
                        'creationDate' => [
                            'type' =>  'date',
                            'format' =>  "yyyy-MM-dd"
                        ],
                        'dateIssued' => [
                            'type' =>  'date',
                            'format' =>  "yyyy-MM-dd"
                        ],
                        'embargoDate' => [
                            'type' =>  'date',
                            'format' =>  "yyyy-MM-dd"
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
     * Adds a document to the index.
     *
     * @param Document $document
     * @throws Exception
     */
    public function index(Document $document)
    {
        $internalFormat = new InternalFormat($document->getXmlData());

        $data = new \stdClass();
        $data->title[] = $document->getTitle();
        $data->doctype = $document->getDocumentType()->getName();
        $data->distribution_date = $internalFormat->getPublishingYear();

        $data->state = $document->getState();
        $data->aliasState = DocumentWorkflow::STATE_TO_ALIASSTATE_MAPPING[$document->getState()];

        if (!$data->doctype) {
            // set document type from database if it has not yet been extracted from XML data
            $data->doctype = $document->getDocumentType()->getName();
        }

        if (!$data->process_number) {
            // set process number from database if it has not yet been extracted from XML data
            $data->process_number = $document->getProcessNumber();
        }

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

            /** @var FrontendUser $creatorFeUser */
            $creatorFeUser = $frontendUserRepository->findByUid($document->getCreator());
            if ($creatorFeUser) {
                $data->creatorRole = $creatorFeUser->getUserRole();
            } else {
                $data->creatorRole = '';
            }
        } else {
            $data->creatorRole = '';
        }

        $creationDate = new DateTime($document->getCreationDate());

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
        if ($embargoDate instanceof DateTime) {
            $data->embargoDate = $embargoDate->format("Y-m-d");
        } else {
            $data->embargoDate = null;
        }

        $data->originalSourceTitle = $internalFormat->getOriginalSourceTitle();

        $data->fobIdentifiers = $internalFormat->getPersonFisIdentifiers();

        // TODO: Is dateIssued the same as distribution date?
        $dateIssued = $internalFormat->getDateIssued();
        if ($dateIssued) {
            $data->dateIssued = date('Y-m-d', strtotime($dateIssued));
        } else {
            $data->dateIssued = null;
        }

        $data->textType             = $internalFormat->getTextType();
        $data->openAccess           = $internalFormat->getOpenAccessForSearch();
        $data->peerReview           = $internalFormat->getPeerReviewForSearch();
        $data->license              = $internalFormat->getLicense();
        $data->frameworkAgreementId = $internalFormat->getFrameworkAgreementId();
        $data->searchYear           = $internalFormat->getSearchYear();
        $data->publisher[]          = $internalFormat->getPublishers();
        $data->collections          = $internalFormat->getCollections();

        $this->client->index([
            'refresh' => 'wait_for',
            'index' => $this->getIndexName(),
            'id' => strtolower($document->getDocumentIdentifier()),
            'body' => $data
        ]);
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
        } catch (Exception $e) {
            /** @var $logger \TYPO3\CMS\Core\Log\Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
            $logger->warning(
                'Document could not be deleted from the index.',
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
