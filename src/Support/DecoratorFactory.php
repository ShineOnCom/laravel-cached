<?php

namespace More\Laravel\Cached\Support;

use More\Laravel\Cached\CacheDecorator;

/**
 * Class DecoratorFactory
 */
class DecoratorFactory
{
    /**
     * @param array $args
     * @return CacheDecorator
     */
    public function __invoke(...$args)
    {
        $class_name = is_object($args[0])
            ? get_class($args[0])
            : $args[0];

        $decorator = defined("{$class_name}::CACHE_DECORATOR")
            ? constant("{$class_name}::CACHE_DECORATOR")
            : config('cached.decorator', CacheDecorator::class);

        return new $decorator(...$args);
    }
}
