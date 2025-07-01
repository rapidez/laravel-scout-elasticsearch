<?php

namespace Rapidez\ScoutElasticSearch\Creator;

interface ProxyInterface
{
    public function getInheritance(): mixed;

    public function getInheritanceKey(): string;
}
