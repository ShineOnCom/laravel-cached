<?php

namespace More\Laravel\Cached\Traits;

/**
 * Trait CacheMayForget
 *
 * USE THIS TRAIT ON MODELS YOU CACHE OR YOUR BASE MODEL
 */
trait CacheMayForget
{
    public static function bootCacheMayForget()
    {
        static::saved(function ($model) {
            $decorator = config('cached.macros.builder.decorate');
            $computed = config('cached.invalidation.forget_computed');
            $tree = config('cached.invalidation.forget_tree');
            $model->$decorator()->forget($touch_self = false, $computed, $tree);
        });
    }
}