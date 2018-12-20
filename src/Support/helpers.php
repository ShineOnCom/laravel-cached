<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;

if (! function_exists('cached')) {
    /**
     * @param string $model_class
     * @param int $id
     * @param string|bool|null $decorator
     * @param string $find_method
     * @return \Illuminate\Database\Eloquent\Model|\More\Laravel\Cached\CacheDecorator
     */
    function cached(string $model_class, int $id, $decorator = null, $find_method = 'find')
    {
        $find = config('cached.macros.builder.cached');
        $decorate = config('cached.macros.builder.decorate');

        $model = call_user_func_array([$model_class, $find], [$id]);

        if ($find_method == 'findOrFail' && empty($model)) {
            throw (new ModelNotFoundException())->setModel($model_class, [$id]);
        }

        if ($decorator === true) {
            return $model->$decorate();
        }

        if ($decorator) {
            return $model->$decorate($decorator);
        }

        return $model;
    }
}

if (! function_exists('cachedOrFail')) {
    /**
     * @param string $model_class
     * @param int $id
     * @param null $decorator
     * @return \Illuminate\Database\Eloquent\Model|\More\Laravel\Cached\CacheDecorator
     */
    function cachedOrFail(string $model_class, int $id, $decorator = null)
    {
        return cached($model_class, $id, $decorator, 'findOrFail');
    }
}