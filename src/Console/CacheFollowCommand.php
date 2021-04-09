<?php

namespace More\Laravel\Cached\Console;

use BadMethodCallException;
use Illuminate\Console\Command;
use More\Laravel\Cached\Jobs\CacheFollowJob;
use More\Laravel\Cached\Support\CachedInterface as CacheInterface;
use More\Laravel\Cached\Models\CacheStub;
use More\Laravel\Cached\Traits\CacheModelDecorator;

/**
 * Class CacheFollowCommand
 */
class CacheFollowCommand extends Command
{
    /** @var string $signature */
    protected $signature = 'cache:follow {decorator} {--ids=any} {--now} {--force}';

    /** @var string $description */
    protected $description = 'Cache database intensive presentation data before it is used.';

    /**
     * @return int
     */
    public function handle()
    {
        /** @var CacheInterface|string $decorator */
        $decorator = $this->argument('decorator');

        if (! in_array(CacheInterface::class, class_implements($decorator))) {
            throw new BadMethodCallException('Decorator must implement '.CacheInterface::class);
        }

        /** @var CacheInterface|CacheModelDecorator $instance */
        $instance = new $decorator;

        // Are we decorating a fake model?
        if ($instance->getModelClass() == CacheStub::class) {
            CacheStub::followInCache($decorator, $this->option('force'));

            return 0;
        }

        $now = $this->option('now');
        $ids = $this->optionIds('ids') ?: $decorator::cacheFollows();

        // Otherwise, we're refreshing cache on a variety of models, and want
        // to do so in a distributed fashion.
        collect($ids)
            ->chunk(config('cached.following.job_chunks'))
            ->each(function($ids_chunk) use ($decorator, $now) {
                $job = new CacheFollowJob($decorator, $ids_chunk);

                $now ? dispatch_now($job) : dispatch($job);
            });

        return 0;
    }

    /**
     * @param $arg
     * @return array|null
     */
    public function optionIds($arg)
    {
        $option = ($this->option($arg));

        if ($option == 'all' || $option == 'any') {
            return null;
        }

        $ids = explode(',', $option);
        $ids = array_map('trim', $ids);
        $ids = array_map('intval', $ids);
        $ids = array_filter($ids);

        return empty($ids) ? null : $ids;
    }
}
