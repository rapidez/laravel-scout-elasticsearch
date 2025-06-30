<?php

namespace Tests;

use App\Product;
use Elastic\Elasticsearch\Client;
use Matchish\ScoutElasticSearch\Creator\ProxyClient;

/**
 * Class IntegrationTestCase.
 */
class IntegrationTestCase extends TestCase
{
    /**
     * @var ProxyClient
     */
    protected $elasticsearch;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->elasticsearch = $this->app->make(ProxyClient::class);

        $this->elasticsearch->indices()->delete(['index' => '_all']);
    }

    protected function isElasticsearch():bool
    {
        return $this->elasticsearch instanceof Client;
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('elasticsearch', require(__DIR__.'/../config/elasticsearch.php'));
        $app['config']->set('elasticsearch.indices.mappings.products', [
            'properties' => [
                'type' => [
                    'type' => 'keyword',
                ],
                'price' => [
                    'type' => 'integer',
                ],
            ],
        ]);
        Product::preventAccessingMissingAttributes();
    }
}
