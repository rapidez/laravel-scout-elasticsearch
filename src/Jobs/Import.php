<?php

namespace Rapidez\ScoutElasticSearch\Jobs;

use Rapidez\ScoutElasticSearch\Creator\ProxyClient;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Rapidez\ScoutElasticSearch\Jobs\Stages\StageInterface;
use Rapidez\ScoutElasticSearch\ProgressReportable;
use Rapidez\ScoutElasticSearch\Searchable\ImportSource;

/**
 * @internal
 */
final class Import
{
    use Queueable;
    use ProgressReportable;

    /**
     * @var ImportSource
     */
    private $source;

    public ?int $timeout = null;

    /**
     * @param  ImportSource  $source
     */
    public function __construct(ImportSource $source)
    {
        $this->source = $source;
    }

    /**
     * @param ProxyClient  $elasticsearch
     */
    public function handle(ProxyClient $elasticsearch): void
    {
        $stages = $this->stages();
        $estimate = $stages->sum->estimate();
        $this->progressBar()->setMaxSteps($estimate);
        $stages->each(function ($stage) use ($elasticsearch) {
            /** @var StageInterface $stage */
            $this->progressBar()->setMessage($stage->title());
            $stage->handle($elasticsearch);
            $this->progressBar()->advance($stage->estimate());
        });
    }

    private function stages(): Collection
    {
        return ImportStages::fromSource($this->source);
    }
}
