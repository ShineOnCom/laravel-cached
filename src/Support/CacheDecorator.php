<?php

namespace More\Laravel\Cached\Support;

use More\Laravel\Cached\Traits\CachesModel;

/**
 * Class CacheDecorator
 */
class CacheDecorator
{
    use CachesModel;

    /**
     * CacheDecorator constructor.
     *
     * @param array $args
     */
    public function __construct(...$args)
    {
        $this->setModel(...$args);
    }
}