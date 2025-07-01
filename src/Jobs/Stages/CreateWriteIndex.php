<?php

namespace Rapidez\ScoutElasticSearch\Jobs\Stages;

use Rapidez\ScoutElasticSearch\Creator\ProxyClient;
use Rapidez\ScoutElasticSearch\ElasticSearch\DefaultAlias;
use Rapidez\ScoutElasticSearch\ElasticSearch\FilteredAlias;
use Rapidez\ScoutElasticSearch\ElasticSearch\Index;
use Rapidez\ScoutElasticSearch\ElasticSearch\Params\Indices\Create;
use Rapidez\ScoutElasticSearch\ElasticSearch\WriteAlias;
use Rapidez\ScoutElasticSearch\Searchable\ImportSource;

/**
 * @internal
 */
final class CreateWriteIndex implements StageInterface
{
    /**
     * @var ImportSource
     */
    private $source;
    /**
     * @var Index
     */
    private $index;

    /**
     * @param  ImportSource  $source
     * @param  Index  $index
     */
    public function __construct(ImportSource $source, Index $index)
    {
        $this->source = $source;
        $this->index = $index;
    }

    public function handle(ProxyClient $elasticsearch): void
    {
        $source = $this->source;
        $this->index->addAlias(
            new FilteredAlias(
                new WriteAlias(new DefaultAlias($source->searchableAs())),
                $this->index
            )
        );

        $params = new Create(
            $this->index->name(),
            $this->index->config()
        );

        $elasticsearch->indices()->create($params->toArray());
    }

    public function title(): string
    {
        return 'Create write index';
    }

    public function estimate(): int
    {
        return 1;
    }
}
