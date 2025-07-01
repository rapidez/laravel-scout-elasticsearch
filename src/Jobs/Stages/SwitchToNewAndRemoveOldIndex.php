<?php

namespace Rapidez\ScoutElasticSearch\Jobs\Stages;

use Rapidez\ScoutElasticSearch\Creator\Helper;
use Rapidez\ScoutElasticSearch\Creator\ProxyClient;
use Rapidez\ScoutElasticSearch\ElasticSearch\Index;
use Rapidez\ScoutElasticSearch\ElasticSearch\Params\Indices\Alias\Get;
use Rapidez\ScoutElasticSearch\ElasticSearch\Params\Indices\Alias\Update;
use Rapidez\ScoutElasticSearch\Searchable\ImportSource;

/**
 * @internal
 */
final class SwitchToNewAndRemoveOldIndex implements StageInterface
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
        $params = Get::anyIndex($source->searchableAs());
        $response = Helper::convertToArray($elasticsearch->indices()->getAlias($params->toArray()));

        $params = new Update();
        foreach ($response as $indexName => $alias) {
            if ($indexName != $this->index->name()) {
                $params->removeIndex((string) $indexName);
            } else {
                $params->add((string) $indexName, $source->searchableAs());
            }
        }
        $elasticsearch->indices()->updateAliases($params->toArray());
    }

    public function estimate(): int
    {
        return 1;
    }

    public function title(): string
    {
        return 'Switching to the new index';
    }
}
