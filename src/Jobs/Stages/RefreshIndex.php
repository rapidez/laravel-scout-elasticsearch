<?php

namespace Rapidez\ScoutElasticSearch\Jobs\Stages;

use Rapidez\ScoutElasticSearch\Creator\ProxyClient;
use Rapidez\ScoutElasticSearch\ElasticSearch\Index;
use Rapidez\ScoutElasticSearch\ElasticSearch\Params\Indices\Refresh;

/**
 * @internal
 */
final class RefreshIndex implements StageInterface
{
    /**
     * @var Index
     */
    private $index;

    /**
     * RefreshIndex constructor.
     *
     * @param  Index  $index
     */
    public function __construct(Index $index)
    {
        $this->index = $index;
    }

    public function handle(ProxyClient $elasticsearch): void
    {
        $params = new Refresh($this->index->name());
        $elasticsearch->indices()->refresh($params->toArray());
    }

    public function estimate(): int
    {
        return 1;
    }

    public function title(): string
    {
        return 'Refreshing index';
    }
}
