<?php

namespace More\Laravel\Cached;

use ArrayAccess;
use BadMethodCallException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Str;
use JsonSerializable;
use More\Laravel\Cached\Support\CachedInterface;
use More\Laravel\Cached\Traits\CacheFollowsDecorator;
use More\Laravel\Cached\Traits\CacheModelDecorator;

/**
 * Class CacheDecorator
 *
 * Presenter pattern introduced by David Hempfield.
 *
 * @see Presenter by David Hempfield (hemp/presenter)
 *
 * @method followInCache()
 * @method static cacheSchedule(\Illuminate\Console\Scheduling\Schedule &$schedule)
 * @method static cacheFollows()
 */
class CacheDecorator implements Jsonable, JsonSerializable, Arrayable, ArrayAccess, CachedInterface
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
     * The attributes that should be visible in arrays.
     *
     * @var array
     */
    protected $visible = [];

    /**
     * The attributes that should be hidden in arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The cache of the mutated attributes for each class.
     *
     * @var array
     */
    protected static $mutatorCache;

    /**
     * Indicates whether attributes are snake cased on arrays.
     *
     * @var bool
     */
    public static $snakeAttributes = true;

    /**
     * The decorated model
     *
     * @var \Illuminate\Database\Eloquent\Model|\More\Laravel\Cached\CacheDecorator
     */
    protected $model;

    /**
     * Get the decorated model
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Pass magic properties to accessors
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        $method = 'get' . studly_case($name) . 'Attribute';

        if (method_exists($this, $method)) {
            return $this->{$method}($name);
        }

        return $this->model->{$name};
    }

    /**
     * Call the model's version of the method if available
     *
     * @param  string $method
     * @param  array $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array([$this->model, $method], $args);
    }

    /**
     * Get the visible attributes for the model.
     *
     * @return array
     */
    public function getVisiblePresenterAttributes()
    {
        return $this->visible;
    }

    /**
     * Get the hidden attributes for the model.
     *
     * @return array
     */
    public function getHiddenPresenterAttributes()
    {
        return $this->hidden;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed data which can be serialized by <b>json_encode</b>,
     *               which is a value of any type other than a resource.
     *
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Convert the decorated instance to a string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Convert the decorated instance to JSON
     *
     * @param  integer $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Convert the decorator instance to an array
     *
     * @return array
     */
    public function toArray()
    {
        if (empty($this->model)) {
            return [];
        }

        $mutatedAttributes = $this->mutatorsToArray();

        $all = array_merge($this->model->toArray(), $mutatedAttributes);

        if (! static::$snakeAttributes) {
            $all = array_combine(
                array_map(function ($k) {
                    return Str::camel($k);
                }, array_keys($all)),
                $all
            );
        }

        $items = $this->getArrayableItems($all);

        if (! static::$snakeAttributes) {
            $items = array_combine(
                array_map(function ($k) {
                    return Str::camel($k);
                }, array_keys($items)),
                $items
            );
        }

        return array_intersect_key($all, $items);
    }

    /**
     * Convert the decorators instance's mutators to an array.
     *
     * @return array
     */
    public function mutatorsToArray()
    {
        $mutatedAttributes = [];

        $mutators = $this->getMutatedAttributes();

        foreach ($mutators as $mutator) {
            $mutatedAttributes[Str::snake($mutator)] = $this->mutateAttribute($mutator);
        }

        return $mutatedAttributes;
    }

    /**
     * Get the mutated attributes for a given instance.
     *
     * @return array
     */
    public function getMutatedAttributes()
    {
        $class = static::class;

        if (!isset(static::$mutatorCache[$class])) {
            static::cacheMutatedAttributes($class);
        }

        return static::$mutatorCache[$class];
    }

    /**
     * Extract and cache all the mutated attributes of a class.
     *
     * @param  string $class
     * @return void
     */
    public static function cacheMutatedAttributes($class)
    {
        $mutatedAttributes = [];

        // Here we will extract all of the mutated attributes so that we can quickly
        // spin through them after we export models to their array form, which we
        // need to be fast. This'll let us know the attributes that can mutate.
        if (preg_match_all('/(?<=^|;)get([^;]+?)Attribute(;|$)/', implode(';', get_class_methods($class)), $matches)) {
            foreach ($matches[1] as $match) {
                if (static::$snakeAttributes) {
                    $match = Str::snake($match);
                }

                $mutatedAttributes[] = lcfirst($match);
            }
        }

        static::$mutatorCache[$class] = $mutatedAttributes;
    }

    /**
     * Get the value of an attribute using its mutator.
     *
     * @param  string $key
     * @return mixed
     */
    protected function mutateAttribute($key)
    {
        return $this->{'get' . Str::studly($key) . 'Attribute'}();
    }

    /**
     * Determine if a get mutator exists for an attribute.
     *
     * @param  string  $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        return method_exists($this, 'get'.Str::studly($key).'Attribute');
    }

    /**
     * Get an attribute array of all arrayable values.
     *
     * @param  array $values
     * @return array
     */
    protected function getArrayableItems($values)
    {
        if (count($this->getVisiblePresenterAttributes()) > 0) {
            $values = array_intersect_key($values, array_flip($this->getVisiblePresenterAttributes()));
        }

        if (count($this->getHiddenPresenterAttributes()) > 0) {
            $values = array_diff_key($values, array_flip($this->getHiddenPresenterAttributes()));
        }

        return $values;
    }

    /**
     * Return true if the property is set and not null.
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    /**
     * Return true if the offset exists and is not null.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->{$offset} !== null;
    }

    /**
     * Return the value at the specified offset.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    /**
     * Required implementation to satisfy the ArrayAccess interface,
     * but throws as a BadMethodCallException as this is a read only
     * implementation.
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     * @throws BadMethodCallException
     */
    public function offsetSet($offset, $value)
    {
        throw new BadMethodCallException('Not implemented - read only implementation.');
    }

    /**
     * Required implementation to satisfy the ArrayAccess interface,
     * but throws as a BadMethodCallException as this is a read only
     * implementation.
     *
     * @param mixed $offset
     * @return void
     * @throws BadMethodCallException
     */
    public function offsetUnset($offset)
    {
        throw new BadMethodCallException('Not implemented - read only implementation.');
    }
}
