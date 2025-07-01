<?php

namespace Rapidez\ScoutElasticSearch\Searchable;

interface ImportSourceFactory
{
    public static function from(string $className): ImportSource;
}
