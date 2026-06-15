<?php
namespace EWW\Dpf\Services\ElasticSearch;

use EWW\Dpf\Domain\Model\Document;
use EWW\Dpf\Services\Api\InternalFormat;
use Throwable;

class PublicElasticSearch extends ElasticSearch
{
    public function __construct()
    {
        parent::__construct();
        $this->indexName = $this->indexName . '_public';
        try {
            $this->initializePublicIndex($this->indexName);
        } catch (Throwable $e) {
            // Index creation failure is non-fatal if it already exists
        }
    }

    protected function initializePublicIndex(string $indexName): void
    {
        if ($this->client->indices()->exists(['index' => $indexName])) {
            return;
        }

        $this->client->indices()->create([
            'index' => $indexName,
            'body' => [
                'settings' => [
                    'analysis' => [
                        'normalizer' => [
                            'lowercase_normalizer' => [
                                'type' => 'custom',
                                'char_filter' => [],
                                'filter' => ['lowercase', 'asciifolding'],
                            ],
                        ],
                    ],
                ],
                'mappings' => [
                    '_source' => ['enabled' => true],
                    'properties' => [
                        'title' => [
                            'type' => 'text',
                            'fields' => [
                                'keyword' => [
                                    'type' => 'keyword',
                                    'normalizer' => 'lowercase_normalizer',
                                ],
                            ],
                        ],
                        'state' => ['type' => 'keyword'],
                        'doctype' => ['type' => 'keyword'],
                        'year' => ['type' => 'integer'],
                        'persons' => [
                            'type' => 'text',
                            'fields' => [
                                'keyword' => [
                                    'type' => 'keyword',
                                    'normalizer' => 'lowercase_normalizer',
                                ],
                            ],
                        ],
                        'personsSort' => [
                            'type' => 'text',
                            'fields' => [
                                'keyword' => [
                                    'type' => 'keyword',
                                    'normalizer' => 'lowercase_normalizer',
                                ],
                            ],
                        ],
                        'collections' => ['type' => 'keyword'],
                        'hasFiles' => ['type' => 'keyword'],
                        'openAccess' => ['type' => 'keyword'],
                        'identifier' => [
                            'type' => 'text',
                            'fields' => [
                                'keyword' => [
                                    'type' => 'keyword',
                                    'normalizer' => 'lowercase_normalizer',
                                ],
                            ],
                        ],
                        'process_number' => ['type' => 'keyword'],
                        'objectIdentifier' => ['type' => 'keyword'],
                        'dateIssued' => ['type' => 'date', 'format' => 'yyyy-MM-dd'],
                        'embargoDate' => ['type' => 'date', 'format' => 'yyyy-MM-dd'],
                        'language' => ['type' => 'keyword'],
                    ],
                ],
            ],
        ]);
    }

    public function index(Document $document, $refresh = 'wait_for')
    {
        $internalFormat = new InternalFormat($document->getXmlData());
        $mapper = new PublicDocumentMapper();
        $data = $mapper->map($document, $internalFormat);
        if ($data === null) {
            return;
        }
        $this->client->index([
            'refresh' => $refresh,
            'index' => $this->getIndexName(),
            'id' => strtolower($document->getDocumentIdentifier()),
            'body' => $data,
        ]);
    }

    /**
     * Indexes a batch of documents using the ES bulk API.
     * Documents rejected by the mapper gate are silently skipped.
     *
     * @param Document[] $documents
     * @param string $refresh
     */
    public function indexBulk(array $documents, string $refresh = 'false'): void
    {
        $mapper = new PublicDocumentMapper();
        $body = [];

        foreach ($documents as $document) {
            $internalFormat = new InternalFormat($document->getXmlData());
            $data = $mapper->map($document, $internalFormat);
            if ($data === null) {
                continue;
            }
            $body[] = ['index' => [
                '_index' => $this->getIndexName(),
                '_id' => strtolower($document->getDocumentIdentifier()),
            ]];
            $body[] = $data;
        }

        if (!empty($body)) {
            $this->client->bulk(['refresh' => $refresh, 'body' => $body]);
        }
    }
}
