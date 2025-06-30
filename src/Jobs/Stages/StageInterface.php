<?php

namespace Matchish\ScoutElasticSearch\Jobs\Stages;

use Matchish\ScoutElasticSearch\Creator\ProxyClient;

interface StageInterface
{
    public function title(): string;

    public function estimate(): int;

    public function handle(ProxyClient $elasticsearch): void;
}
