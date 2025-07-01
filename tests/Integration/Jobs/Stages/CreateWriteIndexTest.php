<?php

declare(strict_types=1);

namespace Tests\Integration\Jobs\Stages;

use App\Product;
use Rapidez\ScoutElasticSearch\Creator\Helper;
use Rapidez\ScoutElasticSearch\Creator\ProxyClient;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Rapidez\ScoutElasticSearch\ElasticSearch\Index;
use Rapidez\ScoutElasticSearch\Jobs\Stages\CreateWriteIndex;
use Rapidez\ScoutElasticSearch\Searchable\DefaultImportSourceFactory;
use Tests\IntegrationTestCase;

final class CreateWriteIndexTest extends IntegrationTestCase
{
    /**
     * @throws ClientResponseException
     * @throws ServerResponseException
     */
    public function test_create_write_index(): void
    {
        /** @var Proxyclient $elasticsearch */
        $elasticsearch = $this->app->make(ProxyClient::class);
        $stage = new CreateWriteIndex(DefaultImportSourceFactory::from(Product::class), Index::fromSource(DefaultImportSourceFactory::from(Product::class)));
        $stage->handle($elasticsearch);
        $response = Helper::convertToArray($elasticsearch->indices()->getAlias(['index' => '*', 'name' => 'products']));
        $this->assertTrue($this->containsWriteIndex($response, 'products'));
    }

    private function containsWriteIndex($response): bool
    {
        foreach ($response as $indexName => $index) {
            foreach ($index['aliases'] as $alias => $data) {
                if ($alias === 'products') {
                    $this->assertIsArray($data);
                    $this->assertArrayHasKey('is_write_index', $data);
                    $this->assertTrue($data['is_write_index']);
                    $this->assertArrayHasKey('filter', $data);
                    $this->assertEquals([
                        'bool' => [
                            'must_not' => [
                                [
                                    'term' => [
                                        '_index' => $indexName,
                                    ],
                                ],
                            ],
                        ],
                    ], $data['filter']);

                    return true;
                }
            }
        }

        return false;
    }
}
