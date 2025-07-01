<?php

namespace Rapidez\ScoutElasticSearch\Jobs\Stages;

use Rapidez\ScoutElasticSearch\Creator\ProxyClient;

interface StageInterface
{
    public function title(): string;

    public function estimate(): int;

    public function handle(ProxyClient $elasticsearch): void;
}
