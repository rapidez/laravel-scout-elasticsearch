<?php

declare(strict_types=1);

namespace Tests\Integration\Jobs\Stages;

use App\Product;
use Rapidez\ScoutElasticSearch\Creator\Helper;
use Rapidez\ScoutElasticSearch\ElasticSearch\Index;
use Rapidez\ScoutElasticSearch\Jobs\Stages\SwitchToNewAndRemoveOldIndex;
use Rapidez\ScoutElasticSearch\Searchable\DefaultImportSourceFactory;
use stdClass;
use Tests\IntegrationTestCase;

final class SwitchToNewAndRemoveOldIndexTest extends IntegrationTestCase
{
    public function test_switch_to_new_and_remove_old_index(): void
    {
        $this->elasticsearch->indices()->create([
            'index' => 'products_new',
            'body' => ['aliases' => ['products' => ['is_write_index' => true]]],
        ]);
        $this->elasticsearch->indices()->create([
            'index' => 'products_old',
            'body' => ['aliases' => ['products' => new stdClass()]],
        ]);

        $stage = new SwitchToNewAndRemoveOldIndex(DefaultImportSourceFactory::from(Product::class), new Index('products_new'));
        $stage->handle($this->elasticsearch);

        $newIndexExist = Helper::convertToBool($this->elasticsearch->indices()->exists(['index' => 'products_new']));
        $oldIndexExist = Helper::convertToBool($this->elasticsearch->indices()->exists(['index' => 'products_old']));
        $alias = Helper::convertToArray($this->elasticsearch->indices()->getAlias(['index' => 'products_new']));

        $this->assertTrue($newIndexExist);
        $this->assertFalse($oldIndexExist);
        $this->assertEquals(['products_new' => [
            'aliases' => ['products' => []],
        ]], $alias);
    }
}
