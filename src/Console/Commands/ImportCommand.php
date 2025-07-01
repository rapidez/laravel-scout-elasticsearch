<?php

declare(strict_types=1);

namespace Rapidez\ScoutElasticSearch\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Rapidez\ScoutElasticSearch\ElasticSearch\Config\Config;
use Rapidez\ScoutElasticSearch\Jobs\Import;
use Rapidez\ScoutElasticSearch\Jobs\QueueableJob;
use Rapidez\ScoutElasticSearch\Searchable\ImportSource;
use Rapidez\ScoutElasticSearch\Searchable\ImportSourceFactory;
use Rapidez\ScoutElasticSearch\Searchable\SearchableListFactory;

final class ImportCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected $signature = 'scout:import {searchable?* : The name of the searchable}';
    /**
     * {@inheritdoc}
     */
    protected $description = 'Create new index and import all searchable into the one';

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $this->searchableList((array) $this->argument('searchable'))
        ->each(function ($searchable) {
            $this->import($searchable);
        });
    }

    private function searchableList(array $argument): Collection
    {
        return collect($argument)->whenEmpty(function () {
            $factory = new SearchableListFactory(app()->getNamespace(), app()->path());

            return $factory->make();
        });
    }

    private function import(string $searchable): void
    {
        $sourceFactory = app(ImportSourceFactory::class);
        $source = $sourceFactory::from($searchable);
        $job = new Import($source);
        $job->timeout = Config::queueTimeout();

        if (config('scout.queue')) {
            $job = (new QueueableJob())->chain([$job]);
            $job->timeout = Config::queueTimeout();
        }

        $bar = (new ProgressBarFactory($this->output))->create();
        $job->withProgressReport($bar);

        $startMessage = trans('scout::import.start', ['searchable' => "<comment>$searchable</comment>"]);
        $this->line($startMessage);

        /* @var ImportSource $source */
        dispatch($job)->allOnQueue($source->syncWithSearchUsingQueue())
            ->allOnConnection($source->syncWithSearchUsing());

        $doneMessage = trans(config('scout.queue') ? 'scout::import.done.queue' : 'scout::import.done', [
            'searchable' => $searchable,
        ]);
        $this->output->success($doneMessage);
    }
}
