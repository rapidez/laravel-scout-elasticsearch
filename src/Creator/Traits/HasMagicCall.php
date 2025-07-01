<?php

namespace Rapidez\ScoutElasticSearch\Creator\Traits;

use Rapidez\ScoutElasticSearch\Exceptions\ProxyMethodNotFound;

trait HasMagicCall
{
    protected bool $reload = true;

    /**
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws ProxyMethodNotFound
     */
    public function __call(string $method, array $parameters)
    {
        if(method_exists($this->getInheritance(), $method)) {
            $inheritanceResponse = $this->getInheritance()->$method(...$parameters);

            return $this->reloadAndReturnResponse($inheritanceResponse);
        }

        throw new ProxyMethodNotFound(sprintf('%s::%s', get_class($this->getInheritance()), $method));
    }

    public function getInheritance(): mixed
    {
        $key = $this->getInheritanceKey();

        return $this->$key;
    }

    protected function reloadAndReturnResponse(mixed $inheritanceResponse): mixed
    {
        if($this->reload && $this->isSameClass($inheritanceResponse)) {
            $key = $this->getInheritanceKey();

            $this->$key = $inheritanceResponse;

            // make sure we always use proxy objects if classes match
            return $this;
        }

        return $inheritanceResponse;
    }

    protected function isSameClass(mixed $inheritanceResponse): bool
    {
        if(is_object($inheritanceResponse)) {

            return get_class($this->getInheritance()) === get_class($inheritanceResponse);
        }

        return false;
    }
}
