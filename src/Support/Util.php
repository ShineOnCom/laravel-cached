<?php

namespace More\Laravel\Cached\Support;

use Illuminate\Console\Scheduling\Schedule;

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
}