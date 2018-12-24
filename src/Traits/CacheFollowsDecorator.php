<?php

namespace More\Laravel\Cached\Traits;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Trait CacheFollowsDecorator
 *
 * Consider extending CacheDecorator to create your own decorators. If that is
 * not possible, you can use the traits, and make sure your __construct()
 * leverages the setModel(...) method.
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
     * @return $this
     */
    public function followInCache()
    {
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
     * @param Schedule $schedule
     * @return Schedule
     */
    public static function cacheSchedule(Schedule &$schedule)
    {
        $interval = defined(get_called_class().'::CACHE_FOLLOWS')
            ? constant(get_called_class().'::CACHE_FOLLOWS')
            : 'daily';

        $schedule->command('cache:follow')->$interval();

        return $schedule;
    }
}