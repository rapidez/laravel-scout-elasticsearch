<?php

namespace Rapidez\ScoutElasticSearch\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Rapidez\ScoutElasticSearch\ProgressReportable;

class QueueableJob implements ShouldQueue
{
    use Queueable;
    use ProgressReportable;

    public ?int $timeout = null;

    public function handle(): void
    {
    }
}
