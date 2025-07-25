<?php

namespace Tests\Integration\Jobs\Stages;

use App\Product;
use Rapidez\ScoutElasticSearch\Creator\Helper;
use Rapidez\ScoutElasticSearch\Jobs\Stages\CleanUp;
use Rapidez\ScoutElasticSearch\Searchable\DefaultImportSourceFactory;
use stdClass;
use Tests\IntegrationTestCase;

class CleanUpTest extends IntegrationTestCase
{
    public function test_remove_write_index()
    {
        $this->elasticsearch->indices()->create([
            'index' => 'products_old',
            'body' => ['aliases' => ['products' => new stdClass()]],
        ]);
        $this->elasticsearch->indices()->create([
            'index' => 'products_new',
            'body' => ['aliases' => ['products' => ['is_write_index' => true], 'products1' => ['is_write_index' => true]]],
        ]);
        $this->elasticsearch->indices()->create([
            'index' => 'products_third',
            'body' => ['aliases' => ['products' => ['is_write_index' => false]]],
        ]);

        $stage = new CleanUp(DefaultImportSourceFactory::from(Product::class));
        $stage->handle($this->elasticsearch);
        $writeIndexExist = Helper::convertToBool($this->elasticsearch->indices()->exists(['index' => 'products_new']));
        $readIndexExist = Helper::convertToBool($this->elasticsearch->indices()->exists(['index' => 'products_old']));

        $this->assertFalse($writeIndexExist);
        $this->assertTrue($readIndexExist);
    }
}
