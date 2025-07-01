<?php

namespace Tests\Unit\ElasticSearch;

use App\Product;
use Rapidez\ScoutElasticSearch\ElasticSearch\Index;
use Rapidez\ScoutElasticSearch\Searchable\DefaultImportSourceFactory;
use Tests\TestCase;

class IndexTest extends TestCase
{
    public function test_creation_from_searchable()
    {
        $index = Index::fromSource(DefaultImportSourceFactory::from(Product::class));
        $this->assertEquals($index->name(), 'products_1525376494');
    }
}

namespace Rapidez\ScoutElasticSearch\ElasticSearch;

function time(): int
{
    return 1525376494;
}
