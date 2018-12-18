<?php

namespace More\Laravel\Cached\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class CacheStub
 */
class CacheStub extends Model
{
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