<?php

namespace Rapidez\ScoutElasticSearch\Engines;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;
use Laravel\Scout\Builder;
use Laravel\Scout\Builder as BaseBuilder;
use Laravel\Scout\Engines\Engine;
use Rapidez\ScoutElasticSearch\Creator\Helper;
use Rapidez\ScoutElasticSearch\Creator\ProxyClient;
use Rapidez\ScoutElasticSearch\ElasticSearch\HitsIteratorAggregate;
use Rapidez\ScoutElasticSearch\ElasticSearch\Params\Bulk;
use Rapidez\ScoutElasticSearch\ElasticSearch\Params\Indices\Refresh;
use Rapidez\ScoutElasticSearch\ElasticSearch\Params\Search as SearchParams;
use Rapidez\ScoutElasticSearch\ElasticSearch\SearchFactory;
use Rapidez\ScoutElasticSearch\ElasticSearch\SearchResults;
use ONGR\ElasticsearchDSL\Query\MatchAllQuery;
use ONGR\ElasticsearchDSL\Search;

final class ElasticSearchEngine extends Engine
{
    /**
     * The ElasticSearch client.
     *
     * @var ProxyClient
     */
    protected ProxyClient $elasticsearch;

    /**
     * Create a new engine instance.
     *
     * @param ProxyClient $elasticsearch
     * @returnvoid
     */
    public function __construct(ProxyClient $elasticsearch)
    {
        $this->elasticsearch = $elasticsearch;
    }

    /**
     * {@inheritdoc}
     */
    public function update($models)
    {
        $params = new Bulk();
        $params->index($models);
        $response = Helper::convertToArray($this->elasticsearch->bulk($params->toArray()));
        if (array_key_exists('errors', $response) && $response['errors']) {
            $error = new ServerResponseException(json_encode($response, JSON_PRETTY_PRINT));
            throw new \Exception('Bulk update error', $error->getCode(), $error);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($models)
    {
        $params = new Bulk();
        $params->delete($models);
        $this->elasticsearch->bulk($params->toArray());
    }

    /**
     * {@inheritdoc}
     */
    public function flush($model)
    {
        $indexName = $model->searchableAs();
        $exist = Helper::convertToBool($this->elasticsearch->indices()->exists(['index' => $indexName]));
        if ($exist) {
            $body = (new Search())->addQuery(new MatchAllQuery())->toArray();
            $params = new SearchParams($indexName, $body);
            $this->elasticsearch->deleteByQuery($params->toArray());
            $this->elasticsearch->indices()->refresh((new Refresh($indexName))->toArray());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function search(BaseBuilder $builder)
    {
        return $this->performSearch($builder, []);
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(BaseBuilder $builder, $perPage, $page)
    {
        return $this->performSearch($builder, [
            'from' => ($page - 1) * $perPage,
            'size' => $perPage,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function mapIds($results)
    {
        return collect($results['hits']['hits'])->pluck('_id');
    }

    /**
     * {@inheritdoc}
     */
    public function map(BaseBuilder $builder, $results, $model)
    {
        $hits = app()->makeWith(
            HitsIteratorAggregate::class,
            [
                'results' => $results,
                'callback' => $builder->queryCallback,
            ]
        );

        return new Collection($hits);
    }

    /**
     * Map the given results to instances of the given model via a lazy collection.
     *
     * @param  \Laravel\Scout\Builder  $builder
     * @param  mixed  $results
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Support\LazyCollection
     */
    public function lazyMap(Builder $builder, $results, $model)
    {
        if ((new \ReflectionClass($model))->isAnonymous()) {
            throw new \Error('Not implemented for MixedSearch');
        }

        if (count($results['hits']['hits']) === 0) {
            return LazyCollection::make($model->newCollection());
        }

        $objectIds = collect($results['hits']['hits'])->pluck('_id')->values()->all();
        $objectIdPositions = array_flip($objectIds);

        return $model->queryScoutModelsByIds(
            $builder, $objectIds
        )->cursor()->filter(function ($model) use ($objectIds) {
            return in_array($model->getScoutKey(), $objectIds);
        })->sortBy(function ($model) use ($objectIdPositions) {
            return $objectIdPositions[$model->getScoutKey()];
        })->values();
    }

    /**
     * Create a search index.
     *
     * @param  string  $name
     * @param  array  $options
     * @return mixed
     */
    public function createIndex($name, array $options = [])
    {
        throw new \Error('Not implemented');
    }

    /**
     * Delete a search index.
     *
     * @param  string  $name
     * @return mixed
     */
    public function deleteIndex($name)
    {
        throw new \Error('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalCount($results)
    {
        return $results['hits']['total']['value'];
    }

    /**
     * @param  BaseBuilder  $builder
     * @param  array  $options
     * @return SearchResults|mixed
     */
    private function performSearch(BaseBuilder $builder, $options = [])
    {
        $searchBody = SearchFactory::create($builder, $options);
        if ($builder->callback) {
            /** @var callable */
            $callback = $builder->callback;

            return call_user_func(
                $callback,
                $this->elasticsearch,
                $searchBody
            );
        }

        $model = $builder->model;
        $indexName = $builder->index ?: $model->searchableAs();
        $params = new SearchParams($indexName, $searchBody->toArray());

        return Helper::convertToArray($this->elasticsearch->search($params->toArray()));
    }
}
