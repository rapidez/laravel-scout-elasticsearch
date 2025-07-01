<?php

declare(strict_types=1);

namespace Rapidez\ScoutElasticSearch;

use Illuminate\Support\ServiceProvider;
use Rapidez\ScoutElasticSearch\Creator\ProxyClient;
use Rapidez\ScoutElasticSearch\Creator\ProxyClientBuilder;
use Rapidez\ScoutElasticSearch\ElasticSearch\Config\Config;
use Rapidez\ScoutElasticSearch\ElasticSearch\EloquentHitsIteratorAggregate;
use Rapidez\ScoutElasticSearch\ElasticSearch\HitsIteratorAggregate;

final class ElasticSearchServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/elasticsearch.php', 'elasticsearch');

        $this->app->bind(ProxyClient::class, function () {
            $clientBuilder = ProxyClientBuilder::create()
                ->setHosts(Config::hosts())
                ->setSSLVerification(Config::sslVerification());
            if ($user = Config::user()) {
                $clientBuilder->setBasicAuthentication($user, Config::password());
            }

            if ($cloudId = Config::elasticCloudId()) {
                $clientBuilder->setElasticCloudId($cloudId)
                    ->setApiKey(Config::apiKey());
            }

            return $clientBuilder->build();
        });

        $this->app->bind(
            HitsIteratorAggregate::class,
            EloquentHitsIteratorAggregate::class
        );
    }

    /**
     * {@inheritdoc}
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/elasticsearch.php' => config_path('elasticsearch.php'),
        ], 'config');
    }

    /**
     * {@inheritdoc}
     */
    public function provides(): array
    {
        return [ProxyClient::class];
    }
}
