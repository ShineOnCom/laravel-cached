<?php

namespace More\Laravel\Cached\Models;

use App\Presenters\Admin\DashboardIndex;
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
        return (new CacheStub())->decorate(DashboardIndex::class);
    }

    /**
     * @param $decorator
     */
    public static function followInCache($decorator)
    {
        return (new static())->decorate($decorator)->followInCache();
    }

    /**
     * @param $decorator
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