<?php

namespace More\Laravel\Cached\Traits;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use More\Laravel\Cached\Models\CacheStub;

/**
 * Trait CacheFollowsDecorator
 *
 * Consider extending CacheDecorator to create your own decorators. If that is
 * not possible, you can use the traits, and make sure your __construct()
 * leverages the setModel(...) method.
 *
 * @mixin CacheModelDecorator
 */
trait CacheFollowsDecorator
{
    /**
     * The `schedule` cron in App\Console\Kernel to replenish cache
     *
     * @var array $cache_follow_props
     */
    public static $cache_follow_props = ['*'];

    /**
     * @return array
     */
    public static function cacheFollows()
    {
        $model_class = (new static)->getModelClass();

        /** @var Builder|Model $model */
        $model = new $model_class;

        return $model->newQuery()
            ->select("{$model->getTable()}.id")
            ->pluck('id')
            ->all();
    }

    /**
     * @param bool $force
     * @return $this
     */
    public function followInCache($force = true)
    {
        if ($this->getModelClass() == CacheStub::class) {
            $attributes = $this->getMutatedAttributes();

            foreach ($attributes as $attribute) {
                if (! $force && $this->cacheHas($attribute)) {
                    continue;
                }

                $this->cachePut(
                    $suffix = $attribute,
                    $value = $this->getModel()->$attribute,
                    $ttl = $this->cacheMinutes($suffix)
                );
            }

            return $this;
        }

        $props = collect(static::$cache_follow_props);

        if ($props->contains('*')) {
            $this->toArray();
            return $this;
        }

        foreach ($props as $prop) {
            $mutator = 'get'.Str::studly($prop).'Attribute';

            if (method_exists($this, $mutator)) {
                $this->$prop;
            } elseif (method_exists($this, $prop)) {
                $this->$prop();
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    protected static function cacheFollowsInterval()
    {
        return defined(get_called_class().'::CACHE_FOLLOWS')
            ? constant(get_called_class().'::CACHE_FOLLOWS')
            : 'daily';
    }

    /**
     * @param Schedule $schedule
     * @param bool $force
     * @return Schedule
     */
    public static function cacheSchedule(Schedule &$schedule, $force = true)
    {
        $interval = static::cacheFollowsInterval();

        $decorator = get_called_class();

        $force = $force ? ' --force' : '';

        $schedule->command("cache:follow \"{$decorator}\"{$force}")->$interval();

        return $schedule;
    }
}