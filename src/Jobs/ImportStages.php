<?php

namespace Rapidez\ScoutElasticSearch\Jobs;

use Illuminate\Support\Collection;
use Rapidez\ScoutElasticSearch\ElasticSearch\Index;
use Rapidez\ScoutElasticSearch\Jobs\Stages\CleanUp;
use Rapidez\ScoutElasticSearch\Jobs\Stages\CreateWriteIndex;
use Rapidez\ScoutElasticSearch\Jobs\Stages\PullFromSource;
use Rapidez\ScoutElasticSearch\Jobs\Stages\RefreshIndex;
use Rapidez\ScoutElasticSearch\Jobs\Stages\SwitchToNewAndRemoveOldIndex;
use Rapidez\ScoutElasticSearch\Searchable\ImportSource;

class ImportStages extends Collection
{
    /**
     * @param  ImportSource  $source
     * @return Collection
     */
    public static function fromSource(ImportSource $source)
    {
        $index = Index::fromSource($source);

        return (new self([
            new CleanUp($source),
            new CreateWriteIndex($source, $index),
            PullFromSource::chunked($source),
            new RefreshIndex($index),
            new SwitchToNewAndRemoveOldIndex($source, $index),
        ]))->flatten()->filter();
    }
}
