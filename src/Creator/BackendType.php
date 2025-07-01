<?php

namespace Rapidez\ScoutElasticSearch\Creator;

use OpenSearch\Client as OpenSearchClient;
use OpenSearch\ClientBuilder as OpenSearchClientBuilder;
use Elastic\Elasticsearch\Client as ElasticsearchClient;
use Elastic\Elasticsearch\ClientBuilder as ElasticsearchClientBuilder;

enum BackendType
{
    case OPENSEARCH;
    case ELASTICSEARCH;

    /**
     * @param string $backend
     * @return BackendType
     */
    public static function load(string $backend): BackendType
    {
        return match(strtolower($backend)) {
            'opensearch' => BackendType::OPENSEARCH,
            default => BackendType::ELASTICSEARCH
        };
    }

    /**
     * @return string
     */
    public function clientClass(): string
    {
        return match ($this) {
            self::OPENSEARCH => OpenSearchClient::class,
            self::ELASTICSEARCH => ElasticsearchClient::class,
        };
    }

    /**
     * @return OpenSearchClientBuilder|ElasticsearchClientBuilder
     */
    public function clientBuilder(): OpenSearchClientBuilder | ElasticsearchClientBuilder
    {
        return match ($this) {
            self::OPENSEARCH => OpenSearchClientBuilder::create(),
            self::ELASTICSEARCH => ElasticsearchClientBuilder::create(),
        };
    }

    /**
     * @return string
     */
    public function clientBuilderClass(): string
    {
        return match ($this) {
            self::OPENSEARCH => OpenSearchClientBuilder::class,
            self::ELASTICSEARCH => ElasticsearchClientBuilder::class,
        };
    }
}
