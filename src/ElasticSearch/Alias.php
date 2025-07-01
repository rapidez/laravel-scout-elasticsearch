<?php

namespace Rapidez\ScoutElasticSearch\ElasticSearch;

interface Alias
{
    public function name(): string;

    public function config(): array;
}
