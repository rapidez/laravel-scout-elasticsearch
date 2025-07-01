<?php

namespace Rapidez\ScoutElasticSearch\Creator;

use Rapidez\ScoutElasticSearch\ElasticSearch\Config\Config;
use OpenSearch\ClientBuilder as OpenSearchClientBuilder;
use Elastic\Elasticsearch\ClientBuilder as ElasticsearchClientBuilder;

class Backend
{
    private function __construct(private readonly BackendType $creator)
    {
    }

    public static function load(): self
    {
        return new self(BackendType::load(Config::backendType()));
    }

    public function clientClass(): string
    {
        return $this->creator->clientClass();
    }

    public function clientBuilder(): OpenSearchClientBuilder | ElasticsearchClientBuilder
    {
        return $this->creator->clientBuilder();
    }

    public function clientBuilderClass(): string
    {
        return $this->creator->clientBuilderClass();
    }
}
