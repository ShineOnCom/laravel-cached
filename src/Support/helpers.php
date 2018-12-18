<?php

if (! function_exists('cached')) {
    /**
     * @param string $model_class
     * @param int $id
     * @param string|bool|null $decorate
     * @return \Illuminate\Database\Eloquent\Model|\More\Laravel\Cached\Support\CacheDecorator
     */
    function cached(string $model_class, int $id, $decorate = null)
    {
        $model = call_user_func_array([$model_class, 'findCached'], [$id]);

        if ($decorate === true) {
            return $model->cached();
        }

        if ($decorate) {
            return $model->cached($decorate);
        }

        return $model;
    }
}