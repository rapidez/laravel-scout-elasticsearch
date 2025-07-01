<?php

declare(strict_types=1);

namespace Rapidez\ScoutElasticSearch;

use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use Laravel\Scout\ScoutServiceProvider;
use Rapidez\ScoutElasticSearch\Console\Commands\FlushCommand;
use Rapidez\ScoutElasticSearch\Console\Commands\ImportCommand;
use Rapidez\ScoutElasticSearch\Creator\ProxyClient;
use Rapidez\ScoutElasticSearch\Engines\ElasticSearchEngine;
use Rapidez\ScoutElasticSearch\Searchable\DefaultImportSourceFactory;
use Rapidez\ScoutElasticSearch\Searchable\ImportSourceFactory;

final class ScoutElasticSearchServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'scout');

        $this->app->make(EngineManager::class)->extend(ElasticSearchEngine::class, function () {
            $elasticsearch = app(ProxyClient::class);

            return new ElasticSearchEngine($elasticsearch);
        });
        $this->registerCommands();
    }

    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        $this->app->register(ScoutServiceProvider::class);
        $this->app->bind(ImportSourceFactory::class, DefaultImportSourceFactory::class);
    }

    /**
     * Register artisan commands.
     *
     * @return void
     */
    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportCommand::class,
                FlushCommand::class,
            ]);
        }
    }
}
