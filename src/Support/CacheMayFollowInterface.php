<?php

namespace More\Laravel\Cached\Support;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface CacheMayFollow
 */
interface CacheMayFollowInterface
{
    /** @return Model|null */
    public function getModel();

    /** @return string */
    public function getModelClass();

    /**
     * Query the models we wish to follow.
     *
     * @return Builder
     */
    public static function cacheFollows();

    /**
     * @return $this
     */
    public function followInCache();

    /**
     * @param Schedule $schedule
     * @return void
     */
    public static function cacheSchedule(Schedule &$schedule);

    /** @return array */
    public function toArray();
}