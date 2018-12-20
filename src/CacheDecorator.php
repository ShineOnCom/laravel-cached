<?php

namespace More\Laravel\Cached;

use More\Laravel\Cached\Support\CacheMayFollowInterface;
use More\Laravel\Cached\Traits\CacheFollowsDecorator;
use More\Laravel\Cached\Traits\CacheModelDecorator;

/**
 * Class CacheDecorator
 */
class CacheDecorator implements CacheMayFollowInterface
{
    use CacheModelDecorator, CacheFollowsDecorator;

    /**
     * CacheDecorator constructor.
     *
     * @param array $args
     */
    public function __construct(...$args)
    {
        $this->setModel(...$args);
    }

    /**
     * @todo Refactor out into Presenter Trait
     * @return array
     */
    public function toArray()
    {
        return $this->getModel()->toArray();
    }
}