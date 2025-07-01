<?php

/**
 * @method setHttpClient($httpClient)
 * @method setAsyncHttpClient($asyncHttpClient)
 * @method setLogger($logger)
 * @method setNodePool($nodePool)
 * @method setHosts(array $hosts)
 * @method setApiKey(string $apiKey, string $id = null)
 * @method setBasicAuthentication(string $username, string $password)
 * @method setElasticCloudId(string $cloudId)
 * @method setRetries(int $retries)
 * @method setSSLCert(string $cert, string $password = null)
 * @method setCABundle(string $cert)
 * @method setSSLKey(string $key, string $password = null)
 * @method setSSLVerification(bool $value = true)
 * @method setElasticMetaHeader(bool $value = true)
 * @method setHttpClientOptions(array $options)
 */

namespace Rapidez\ScoutElasticSearch\Creator;

use Elastic\Elasticsearch\ClientBuilder as ElasticsearchClientBuilder;
use Rapidez\ScoutElasticSearch\Creator\Traits\HasMagicCall;
use OpenSearch\ClientBuilder as OpenSearchClientBuilder;

final class ProxyClientBuilder implements ProxyInterface
{
    use HasMagicCall;

    private function __construct(private ElasticsearchClientBuilder|OpenSearchClientBuilder $clientBuilder)
    {
    }

    public static function create(): self
    {
        return new self(Backend::load()->clientBuilder());
    }

    public static function fromConfig(array $config, bool $quiet = false): ProxyClient
    {
        return new ProxyClient(Backend::load()->clientBuilderClass()::fromConfig($config, $quiet));
    }

    public function build(): ProxyClient
    {
        return new ProxyClient($this->clientBuilder->build());
    }

    public function getInheritanceKey(): string
    {
        return 'clientBuilder';
    }
}
