<?php

namespace More\Laravel\Cached\Support;

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

        if (defined("{$class_name}::CACHE_DECORATOR")) {
            $decorator = constant("{$class_name}::CACHE_DECORATOR");
        } else {
            $decorator = config('cached.decorator', CacheDecorator::class);
        }

        return new $decorator(...$args);
    }
}
