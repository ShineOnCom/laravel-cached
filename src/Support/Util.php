<?php

namespace More\Laravel\Cached\Support;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Model;
use More\Laravel\Cached\CacheDecorator;

/**
 * Class Util
 */
class Util
{
    /**
     * @param Schedule $schedule
     */
    public static function bindCacheFollowingSchedule(Schedule &$schedule)
    {
        if (count($cache_follows = config('cached.following.follows', []))) {
            foreach ($cache_follows as $decorator) {
                $decorator::cacheSchedule($schedule);
            }
        }
    }

    /**
     * @param $model_class
     * @param null $model_id
     * @return string
     */
    public static function cacheKeyBaseModel($model_class, $model_id = null)
    {
        if (is_object($model_class)) {
            /** @var Model $model_class */
            $model_id = $model_class->getKey();
            $model_class = get_class($model_class);
        }

        // Return cache key for the base model
        $version = defined($cache_version = $model_class . "::CACHE_VERSION")
            ? constant($cache_version)
            : 'NO_CACHE_VERSION_DEFINED';

        $base_name = class_basename($model_class);

        return "$base_name/{$model_id}-$version";
    }

    /**
     * @param string $suffix
     * @param CachedInterface $decorator
     * @return string
     */
    public static function cacheKey($decorator, $suffix = '')
    {
        $base = static::cacheKeyBaseModel($decorator->getModelClass(), $decorator->getModelId());

        if ($suffix == '') {
            return $base;
        }

        // Return cache key for one of the base models properties
        $tail = implode("-", array_filter([
            $suffix,                                // attribute, relation or method name
            $decorator->getModelVersionAccessor(),  // by default, `updated_at`
        ]));

        return "$base-$tail";
    }
}