<?php

namespace More\Laravel\Cached\Traits;

use Cache;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use More\Laravel\Cached\Models\CacheStub;
use More\Laravel\Cached\Support\CachedInterface;
use More\Laravel\Cached\Support\Util;

/**
 * Trait CacheModelDecorator
 *
 * Consider extending CacheDecorator to create your own decorators. If that is
 * not possible, you can use the traits, and make sure your __construct()
 * leverages the setModel(...) method.
 *
 * @method __construct(Model|string $model, int $model_id = null)
 * @mixin CachedInterface
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
     * @return int|string|null
     */
    public function getModelId()
    {
        return $this->model_id;
    }

    /** @return string */
    public function getModelVersionAccessor()
    {
        return $this->model_version_accessor;
    }

    /**
     * @param mixed ...$args
     * @return $this
     */
    protected function setModel(...$args)
    {
        $model_accessor = $this->model_accessor;

        $model = $args[0] ?? null;

        if (empty($model) || is_object($model)) {
            $this->$model_accessor = $this->model = $model;
            if ($this->model) {
                $this->setModelClass(get_class($model))
                    ->setModelId($model->getKey());
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
     * @param int|string|null $model_id
     * @return $this
     */
    public function setModelId($model_id)
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
        $seconds = $this->cacheSeconds($suffix);

        Cache::put($key, $value, $seconds);

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
            $this->cacheSeconds(),
            $data);
    }

    /**
     * @param string $suffix
     * @return string
     */
    protected function cacheKey($suffix = '')
    {
        return Util::cacheKey($this, $suffix);
    }

    /**
     * @param string $suffix
     * @return int|mixed
     */
    protected function cacheSeconds($suffix = '')
    {
        return isset(static::$cache_times[$suffix])
            ? static::$cache_times[$suffix]
            : defined("{$this->model_class}::CACHE_TIME")
                ? constant("{$this->model_class}::CACHE_TIME")
                : 1440 * 60;
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
     * @param bool $touch_self
     * @param bool $computed
     * @param bool $tree
     * @return $this
     */
    public function forget($touch_self = false, $computed = false, $tree = false)
    {
        Cache::forget($this->cacheKey());

        if ($touch_self) {
            $this->getModel()->touch();
        }

        if ($computed) {
            $args = is_array($computed) ? $computed : [];
            $this->forgetComputed(...$args);
        }

        if ($tree) {
            $this->forgetTree();
        }

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
     * Use with care.
     *
     * Make sure your models only touch relations downstream or upstream.
     *
     * Avoid recursive touching!
     *
     * Make sure your relations don't have thousands of models.
     *
     * @param bool $touch
     * @return $this
     */
    public function forgetTree($touch = false)
    {
        $decorate = config('cached.macros.builder.decorate');

        foreach ($this->getModel()->touches as $relation) {
            $related = $this->getModel()->$relation;

            if (is_a($related, get_class($this->getModel()))) {
                /** @var Model $related */
                $related->fireModelEvent('saved', false);
                $related->$decorate(get_class($this))
                    ->forget($touch, $computed = true, $tree = false)
                    ->forgetTree($touch);
            } elseif ($related instanceof Collection) {
                $related->each(function (Model $related_model) use ($decorate, $touch) {
                    $related_model->$decorate()
                        ->forget($touch, $computed = true, $tree = false)
                        ->forgetTree($touch);
                });
            }
        }

        return $this;
    }
}