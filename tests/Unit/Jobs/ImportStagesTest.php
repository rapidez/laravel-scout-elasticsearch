<?php

namespace Tests\Unit\Jobs;

use App\Product;
use Rapidez\ScoutElasticSearch\Jobs\ImportStages;
use Rapidez\ScoutElasticSearch\Jobs\Stages\CleanUp;
use Rapidez\ScoutElasticSearch\Jobs\Stages\CreateWriteIndex;
use Rapidez\ScoutElasticSearch\Jobs\Stages\PullFromSource;
use Rapidez\ScoutElasticSearch\Jobs\Stages\RefreshIndex;
use Rapidez\ScoutElasticSearch\Jobs\Stages\SwitchToNewAndRemoveOldIndex;
use Rapidez\ScoutElasticSearch\Searchable\DefaultImportSourceFactory;
use Tests\TestCase;

class ImportStagesTest extends TestCase
{
    public function test_no_pull_stages_if_no_searchables()
    {
        $stages = ImportStages::fromSource(DefaultImportSourceFactory::from(Product::class));
        $this->assertEquals(4, $stages->count());
        $this->assertInstanceOf(CleanUp::class, $stages->get(0));
        $this->assertInstanceOf(CreateWriteIndex::class, $stages->get(1));
        $this->assertInstanceOf(RefreshIndex::class, $stages->get(2));
        $this->assertInstanceOf(SwitchToNewAndRemoveOldIndex::class, $stages->get(3));
    }

    public function test_stages()
    {
        factory(Product::class, 10)->create();
        $stages = ImportStages::fromSource(DefaultImportSourceFactory::from(Product::class));
        $this->assertEquals(8, $stages->count());
        $this->assertInstanceOf(CleanUp::class, $stages->get(0));
        $this->assertInstanceOf(CreateWriteIndex::class, $stages->get(1));
        $this->assertInstanceOf(PullFromSource::class, $stages->get(2));
        $this->assertInstanceOf(PullFromSource::class, $stages->get(3));
        $this->assertInstanceOf(PullFromSource::class, $stages->get(4));
        $this->assertInstanceOf(PullFromSource::class, $stages->get(5));
        $this->assertInstanceOf(RefreshIndex::class, $stages->get(6));
        $this->assertInstanceOf(SwitchToNewAndRemoveOldIndex::class, $stages->get(7));
    }
}
