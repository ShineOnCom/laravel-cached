<?php

namespace More\Laravel\Cached\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use More\Laravel\Cached\CacheDecorator;
use More\Laravel\Cached\Support\CachedInterface;

/**
 * Class CacheFollowJob
 */
class CacheFollowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** @var CacheDecorator|string $decorator */
    protected $decorator;

    /** @var array $ids */
    protected $ids;

    /** @var Collection $collection */
    protected $collection;

    /**
     * CacheFollowJob constructor.
     *
     * @param string $decorator
     * @param array $ids
     */
    public function __construct($decorator, $ids)
    {
        $this->decorator = $decorator;
        $this->ids = $ids;
    }

    /**
     * @return $this
     */
    public function handle()
    {
        $decorator = $this->decorator;
        /** @var Model|Builder|string $model_class */
        $model = $decorator->getModelClass();

        foreach ($this->ids as $id) {
            /** @var CachedInterface $decorated */
            $decorated = cached($model, $id, $decorator);

            $decorated->followInCache();

            $this->collection->push($decorated);
        }

        return $this;
    }

    /**
     * @return Collection
     */
    public function getCollection(): Collection
    {
        return $this->collection;
    }
}