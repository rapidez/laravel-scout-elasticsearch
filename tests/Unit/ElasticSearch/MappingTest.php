<?php

namespace Tests\Unit\ElasticSearch;

use Rapidez\ScoutElasticSearch\ElasticSearch\Index;
use Tests\TestCase;

class MappingTest extends TestCase
{
    protected const ELASTICSEARCH_MAPPING = [
        'properties' => [
            'flattened_field' => [
                'type' => 'flattened',
            ],
            'dense_vector_field' => [
                'type' => 'dense_vector',
                'dims' => 128,
            ],
        ],
    ];

    protected const OPENSEARCH_MAPPING = [
        'properties' => [
            'flattened_field' => [
                'type' => 'flat_object',
            ],
            'dense_vector_field' => [
                'type' => 'knn_vector',
                'dims' => 128,
            ],
        ],
    ];

    public function test_elasticsearch_mapping_to_opensearch_mapping()
    {
        $this->assertEquals(
            self::OPENSEARCH_MAPPING,
            Index::transformElasticsearchToOpensearchMapping(
                self::ELASTICSEARCH_MAPPING
            )
        );
    }

    public function test_opensearch_mapping_to_elasticsearch_mapping()
    {
        $this->assertEquals(
            self::ELASTICSEARCH_MAPPING,
            Index::transformElasticsearchToOpensearchMapping(
                self::OPENSEARCH_MAPPING
            )
        );
    }
}
