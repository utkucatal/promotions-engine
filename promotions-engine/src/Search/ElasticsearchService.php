<?php

namespace App\Search;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\AuthenticationException;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\MissingParameterException;
use Elastic\Elasticsearch\Exception\ServerResponseException;

class ElasticsearchService
{
    private Client $client;

    /**
     * @throws AuthenticationException
     */
    public function __construct(string $elasticsearchUrl){
        $this->client = ClientBuilder::create()
            ->setHosts([$elasticsearchUrl])
            ->build();
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     * @throws MissingParameterException
     */
    public function createIndices(): void
    {
        if(!$this->client->indices()->exists(['index' => 'products'])->asBool()){
            $this->client->indices()->create([
                'index' => 'products',
                'body' => [
                    'mappings' => [
                        'properties' => [
                            'name' => ['type' => 'text'], // for full text
                            'sku' => ['type' => 'keyword'], // for exactly search
                            'description' => ['type' => 'text'],
                            'price' => ['type' => 'float'],
                        ]
                    ]
                ]
            ]);

            if (!$this->client->indices()->exists(['index' => 'promotions'])->asBool()) {
                $this->client->indices()->create([
                    'index' => 'promotions',
                    'body' => [
                        'mappings' => [
                            'properties' => [
                                'name' => ['type' => 'text'],
                                'type' => ['type' => 'keyword'],
                                'adjustment' => ['type' => 'float'],
                                'criteria' => ['type' => 'object'],
                            ]
                        ]
                    ]
                ]);
            }
        }
    }


    public function index(string $indexName, int $id, array $data): void
    {
        $this->client->index([
            'index' => $indexName,
            'id' => $id,
            'body' => $data
        ]);
    }

    public function bulkIndex(string $indexName, array $documents, int $chunkSize = 20000): void
    {
        foreach (array_chunk($documents, $chunkSize) as $chunk) {
            $params = ['body' => []];

            foreach ($chunk as $doc) {
                $params['body'][] = [
                    'index' => [
                        '_index' => $indexName,
                        '_id' => $doc['id'],
                    ]
                ];
                $params['body'][] = $doc;
            }
            $this->client->bulk($params);
        }
    }

    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function search(string $indexName, string $query, array $filters = []): array
    {
        $must = [
            'multi_match' => [
                'query' => $query,
                'fields' => ['name^2', 'description', 'sku'],
                'fuzziness' => 'AUTO',
            ]
        ];

        $filter = [];
        foreach ($filters as $field => $value) {
            $filter[] = ['term' => [$field => $value]];
        }

        $response = $this->client->search([
            'index' => $indexName,
            'body' => [
                'query' => [
                    'bool' => [
                        'must' => [$must],
                        'filter' => $filter
                    ]
                ]
            ]
        ]);

        return $response->asArray()['hits']['hits'];
    }

    /**
     * @throws ServerResponseException
     * @throws ClientResponseException
     * @throws MissingParameterException
     */
    public function delete(string $indexName, int $id): void
    {
        $this->client->delete([
            'index' => $indexName,
            'id' => $id,
        ]);
    }
}
