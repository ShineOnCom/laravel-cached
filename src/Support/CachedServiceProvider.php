<?php

namespace More\Laravel\Cached\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\ServiceProvider;

/**
 * Class CachedServiceProvider
 *
 * @mixin Builder
 */
class CachedServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/cached.php' => config_path('cached.php'),
        ], 'config');

        $factory = new DecoratorFactory;

        Builder::macro('decorate', function ($decorator = null) use ($factory) {
           return $decorator
               ? new $decorator($this->getModel())
               : $factory($this->getModel());
        });

        Builder::macro('findCached', function ($id, $columns = ['*']) use ($factory) {
            return $factory(get_class($this->getModel()), $id)->getModel();
        });
    }

    public function register()
    {

    }
}
