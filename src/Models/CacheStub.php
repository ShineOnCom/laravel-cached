<?php

namespace More\Laravel\Cached\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use More\Laravel\Cached\CacheDecorator;

/**
 * Class CacheStub
 */
class CacheStub extends Model
{
    /**
     * @param $decorator
     * @return CacheDecorator
     */
    public static function make($decorator)
    {
        return (new CacheStub())->decorate($decorator);
    }

    /**
     * By default, we only add values to cache that are not already there.
     *
     * Use force to refresh cache value.
     *
     * @param $decorator
     * @param $force
     * @return CacheDecorator
     */
    public static function followInCache($decorator, $force = false)
    {
        static::forget($decorator);

        return (new static())->decorate($decorator)->followInCache($force);
    }

    /**
     * @param $decorator
     * @return CacheDecorator
     */
    public static function forget($decorator)
    {
        return (new static())->decorate($decorator)->forget();
    }

    /**
     * @param $id
     * @param array $columns
     * @return CacheStub
     */
    public static function find($id, $columns = ['*'])
    {
        return new static();
    }

    /**
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = [])
    {
        return false;
    }

    /**
     * Update the model in the database.
     *
     * @param  array  $attributes
     * @param  array  $options
     * @return bool
     */
    public function update(array $attributes = [], array $options = [])
    {
        return false;
    }

    /**
     * @return int
     */
    public function getKey()
    {
        return 0;
    }
}