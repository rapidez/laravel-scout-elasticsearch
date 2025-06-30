<?php

namespace Matchish\ScoutElasticSearch\Jobs\Stages;

use Matchish\ScoutElasticSearch\Creator\Helper;
use Matchish\ScoutElasticSearch\Creator\ProxyClient;
use Matchish\ScoutElasticSearch\ElasticSearch\Index;
use Matchish\ScoutElasticSearch\ElasticSearch\Params\Indices\Alias\Get;
use Matchish\ScoutElasticSearch\ElasticSearch\Params\Indices\Alias\Update;
use Matchish\ScoutElasticSearch\Searchable\ImportSource;

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
