<?php

namespace Matchish\ScoutElasticSearch\ElasticSearch;

use Matchish\ScoutElasticSearch\ElasticSearch\Config\Config;
use Matchish\ScoutElasticSearch\Searchable\ImportSource;

/**
 * @internal
 */
final class Index
{
    public const ELASTICSEARCH_TO_OPENSEARCH_FIELD_TYPE = [
        'flattened' => 'flat_object',
        'dense_vector' => 'knn_vector',
    ];

    /**
     * @var array
     */
    private $aliases = [];

    /**
     * @var string
     */
    private $name;
    /**
     * @var array|null
     */
    private $settings;
    /**
     * @var array|null
     */
    private $mappings;

    /**
     * Index constructor.
     *
     * @param  string  $name
     * @param  array  $settings
     * @param  array  $mappings
     */
    public function __construct(string $name, ?array $settings = null, ?array $mappings = null)
    {
        $this->name = $name;
        $this->settings = $settings;
        $this->mappings = self::transformMapping($mappings);
    }

    /**
     * @return array
     */
    public function aliases(): array
    {
        return $this->aliases;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @param  Alias  $alias
     */
    public function addAlias(Alias $alias): void
    {
        $this->aliases[$alias->name()] = $alias->config() ?: new \stdClass();
    }

    /**
     * @return array
     */
    public function config(): array
    {
        $config = [];
        if (! empty($this->settings)) {
            $config['settings'] = $this->settings;
        }
        if (! empty($this->mappings)) {
            $config['mappings'] = $this->mappings;
        }
        if (! empty($this->aliases())) {
            $config['aliases'] = $this->aliases();
        }

        return $config;
    }

    public static function fromSource(ImportSource $source): Index
    {
        $name = $source->searchableAs().'_'.time();
        $settingsConfigKey = "elasticsearch.indices.settings.{$source->searchableAs()}";
        $mappingsConfigKey = "elasticsearch.indices.mappings.{$source->searchableAs()}";
        $defaultSettings = [
            'number_of_shards' => 1,
            'number_of_replicas' => 0,

        ];
        $settings = config($settingsConfigKey, config('elasticsearch.indices.settings.default', $defaultSettings));
        $mappings = config($mappingsConfigKey, config('elasticsearch.indices.mappings.default'));

        return new static($name, $settings, $mappings);
    }

    public static function transformMapping(?array $mappings): ?array
    {
        if (!$mappings) {
            return $mappings;
        }

        if(Config::backendType() === 'elasticsearch') {
            return self::transformOpensearchToElasticsearchMapping($mappings);
        }
        return self::transformElasticsearchToOpensearchMapping($mappings);
    }

    public static function transformElasticsearchToOpensearchMapping(?array $mappings): ?array
    {
        if (!$mappings) {
            return $mappings;
        }

        $opensearchMapping = self::ELASTICSEARCH_TO_OPENSEARCH_FIELD_TYPE;

        $mappings['properties'] = array_map(function ($mapping) use ($opensearchMapping) {
            $type = $opensearchMapping[$mapping['type'] ?? ''] ?? '';
            if (!$type) {
                return $mapping;
            }
            if ($mapping['type'] === 'dense_vector' && $type === 'knn_vector') {
                $mapping['data_type'] = $mapping['element_type'] ?? null;
                $mapping['dimension'] = $mapping['dims'] ?? null;
                unset($mapping['element_type']);
                unset($mapping['dims']);
                $mapping = array_filter($mapping);
            }

            $mapping['type'] = $type;

            return $mapping;
        }, $mappings['properties'] ?? []);

        return $mappings;
    }

    public static function transformOpensearchToElasticsearchMapping(?array $mappings): ?array
    {
        if (!$mappings) {
            return $mappings;
        }

        $opensearchMapping = array_flip(self::ELASTICSEARCH_TO_OPENSEARCH_FIELD_TYPE);

        $mappings['properties'] = array_map(function ($mapping) use ($opensearchMapping) {
            $type = $opensearchMapping[$mapping['type'] ?? ''] ?? '';
            if (!$type) {
                return $mapping;
            }
            if ($mapping['type'] === 'dense_vector' && $type === 'knn_vector') {
                $mapping['element_type'] = $mapping['data_type'];
                $mapping['dims'] = $mapping['dimension'];
                unset($mapping['data_type']);
                unset($mapping['dimension']);
                array_filter($mapping);
                $mapping = array_filter($mapping);
            }

            $mapping['type'] = $type;

            return $mapping;
        }, $mappings['properties'] ?? []);

        return $mappings;
    }
}
