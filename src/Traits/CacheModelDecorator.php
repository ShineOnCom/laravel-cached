<?php

namespace More\Laravel\Cached\Traits;

use Cache;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use More\Laravel\Cached\Models\CacheStub;

/**
 * Trait CacheModelDecorator
 *
 * Turn any class into a model cache decorator with a shared key system.
 *
 * @method __construct(Model|string $model, int $model_id = null)
 * @property Model $model
 */
trait CacheModelDecorator
{
    /** @var array $cache_times */
    public static $cache_times = [
        '*' => 1440
        // 'attribute_mutator' => 123 // minutes
    ];

    /** @var string $model_class */
    protected $model_class = CacheStub::class;

    /** @var int|null $model_id */
    protected $model_id = null;

    /** @var string $model_accessor */
    protected $model_accessor = 'model';

    /** @var string $model_version_accessor */
    protected $model_version_accessor = 'updated_at';

    /**
     * @return Model|null
     */
    public function getModel(): ?Model
    {
        return $this->model;
    }
    /**
     * @return string
     */
    public function getModelClass(): string
    {
        return $this->model_class;
    }

    /**
     * @return int|null
     */
    public function getModelId(): ?int
    {
        return $this->model_id;
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    protected function setModel(...$args)
    {
        $model_accessor = $this->model_accessor;

        if (is_object($args[0]) || is_null($args[0])) {
            $this->$model_accessor = $this->model = $args[0];
            if ($this->model) {
                $this->setModelClass(get_class($this->model))
                    ->setModelId($this->model->getKey());
            }
        } else {
            $this->setModelClass(array_shift($args))
                ->setModelId(array_first($args))
                ->setModel(call_user_func_array([$this, 'find'], $args));
        }

        return $this;
    }

    /**
     * @param string $model_class
     * @return $this
     */
    public function setModelClass($model_class)
    {
        $this->model_class = $model_class;

        return $this;
    }

    /**
     * @param int|null $model_id
     * @return $this
     */
    public function setModelId(?int $model_id)
    {
        $this->model_id = $model_id;

        return $this;
    }

    /**
     * @param $value
     * @param string $suffix
     * @return $this
     */
    protected function cache($value, $suffix = '')
    {
        $key = $this->cacheKey($suffix);
        $minutes = $this->cacheMinutes($suffix);

        Cache::put($key, $value, $minutes);

        return $this;
    }

    /**
     * @param Closure $data
     * @param $suffix
     * @return mixed
     */
    protected function cached(Closure $data, $suffix)
    {
        return Cache::remember(
            $this->cacheKey($suffix),
            $this->cacheMinutes(),
            $data);
    }

    /**
     * @param string $suffix
     * @return string
     */
    protected function cacheKey($suffix = '')
    {
        $model_accessor = $this->model_accessor;

        return class_basename($this->model_class)."/"
            .implode("-", array_filter([
                $this->model_id ?: $this->$model_accessor->getKey(),
                $suffix,
                $this->model_version_accessor
            ]));
    }

    /**
     * @param string $suffix
     * @return int|mixed
     */
    protected function cacheMinutes($suffix = '')
    {
        return isset(static::$cache_times[$suffix])
            ? static::$cache_times[$suffix]
            : defined("{$this->model_class}::CACHE_TIME")
                ? constant("{$this->model_class}::CACHE_TIME")
                : 1440;
    }

    /**
     * @param Closure $data
     * @return mixed
     */
    protected function cachedAttribute(Closure $data)
    {
        $mutator = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2)[1]['function'];
        $mutator = Str::snake(substr($mutator, 3, strlen($mutator) - 12));

        return $this->cached($data, $mutator);
    }

    /**
     * @param array $args
     * @return Model|null
     */
    public function find(...$args)
    {
        array_unshift($args, __FUNCTION__);
        return $this->findCached(...$args);
    }

    /**
     * @param array $args
     * @return Model
     */
    public function findOrFail(...$args)
    {
        array_unshift($args, __FUNCTION__);
        return $this->findCached(...$args);
    }

    /**
     * @param string $find_method
     * @param int $id
     * @param string|bool|null $decorator
     * @return mixed
     */
    public function findCached($find_method, $id, $decorator = null)
    {
        $model = $this->cached(function() use (&$args, $find_method, $id) {
            $model = call_user_func_array([$this->model_class, $find_method], [$id]);

            if ($model) {
                $this->cache($model, $empty_key_for_model = '');
            }

            return $model;
        }, $empty_key_for_model = '');

        if ($decorator === true) {
            return $this->setModel($model);
        } elseif ($decorator) {
            return new $decorator($model);
        }

        return $model;
    }

    /**
     * @return $this
     */
    public function forget()
    {
        $this->model->touch();

        $this->forgetComputed();

        return $this;
    }

    /**
     * @param array $suffixes
     * @return $this
     */
    public function forgetComputed(...$suffixes)
    {
        $model_accessor = $this->model_accessor;

        $attributes = empty($suffixes) || array_first(static::$cache_times) == '*'
            ? $this->$model_accessor->getMutatedAttributes()
            : array_keys(static::$cache_times);

        foreach ($attributes as $attribute) {
            Cache::forget($attribute);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function forgetTree()
    {
        $this->forget();

        foreach ($this->model->touches as $relation) {
            $this->$relation()->touch();

            if ($this->$relation instanceof self) {
                $this->$relation->fireModelEvent('saved', false);

                (new static($this->$relation))->forgetTree();
            } elseif ($this->$relation instanceof Collection) {
                $this->$relation->each(function (Model $relation) {
                    (new static($this->$relation))->forgetTree();
                });
            }
        }

        return $this;
    }
}