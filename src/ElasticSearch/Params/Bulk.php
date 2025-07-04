<?php

namespace Rapidez\ScoutElasticSearch\ElasticSearch\Params;

/**
 * @internal
 */
final class Bulk
{
    /**
     * @var array
     */
    private $indexDocs = [];

    /**
     * @var array
     */
    private $deleteDocs = [];

    /**
     * @param  array|object  $docs
     */
    public function delete($docs): void
    {
        if (is_iterable($docs)) {
            foreach ($docs as $doc) {
                $this->delete($doc);
            }
        } else {
            $this->deleteDocs[$docs->getScoutKey()] = $docs;
        }
    }

    /**
     * TODO: Add ability to extend payload without modifying the class.
     *
     * @return array
     */
    public function toArray(): array
    {
        $payload = ['body' => []];
        $payload = collect($this->indexDocs)->reduce(
            function ($payload, $model) {
                if (config('scout.soft_delete', false) && $model::usesSoftDelete()) {
                    $model->pushSoftDeleteMetadata();
                }

                $attributes = $model->getAttributes();
                $routing = array_key_exists('routing', $attributes) ? $model->routing : null;
                $scoutKey = $model->getScoutKey();

                $payload['body'][] = [
                    'index' => [
                        '_index' => $model->searchableAs(),
                        '_id' => $scoutKey,
                        'routing' => false === empty($routing) ? $routing : $scoutKey,
                    ],
                ];

                $payload['body'][] = array_merge(
                    $model->toSearchableArray(),
                    $model->scoutMetadata(),
                    [
                        '__class_name' => get_class($model),
                    ]
                );

                return $payload;
            }, $payload);

        $payload = collect($this->deleteDocs)->reduce(
            function ($payload, $model) {
                $attributes = $model->getAttributes();
                $routing = array_key_exists('routing', $attributes) ? $model->routing : null;
                $scoutKey = $model->getScoutKey();

                $payload['body'][] = [
                    'delete' => [
                        '_index' => $model->searchableAs(),
                        '_id' => $scoutKey,
                        'routing' => false === empty($routing) ? $routing : $scoutKey,
                    ],
                ];

                return $payload;
            }, $payload);

        return $payload;
    }

    /**
     * @param  array|object  $docs
     */
    public function index($docs): void
    {
        if (is_iterable($docs)) {
            foreach ($docs as $doc) {
                $this->index($doc);
            }
        } else {
            $this->indexDocs[$docs->getScoutKey()] = $docs;
        }
    }
}
