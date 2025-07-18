<?php

declare(strict_types=1);

namespace Tests\Integration\Jobs\Stages;

use Rapidez\ScoutElasticSearch\Creator\Helper;
use Rapidez\ScoutElasticSearch\ElasticSearch\Index;
use Rapidez\ScoutElasticSearch\Jobs\Stages\RefreshIndex;
use stdClass;
use Tests\IntegrationTestCase;

final class RefreshIndexTest extends IntegrationTestCase
{
    public function test_refresh_index(): void
    {
        $this->elasticsearch->indices()->create([
            'index' => 'products_index',
            'body' => ['aliases' => ['products' => new stdClass()]],
        ]);
        $this->elasticsearch->bulk(['body' => [
            ['index' => [
                '_index' => 'products',
                '_id' => 'id',
            ]],
            [
                'id' => 1,
                'title' => 'Scout',
            ], ],
        ]);

        $stage = new RefreshIndex(new Index('products_index'));
        $stage->handle($this->elasticsearch);

        $params = [
            'index' => 'products',
            'body' => [
                'query' => [
                    'match_all' => new stdClass(),
                ],
            ],
        ];
        $response = Helper::convertToArray($this->elasticsearch->search($params));
        $this->assertEquals(1, $response['hits']['total']['value']);
    }
}
