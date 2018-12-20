<?php

namespace More\Laravel\Cached\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\ServiceProvider;
use More\Laravel\Cached\Console\CacheFollowCommand;
use More\Laravel\Cached\Support\DecoratorFactory;
use More\Laravel\Cached\Traits\CacheModelDecorator;

/**
 * Class CachedServiceProvider
 *
 * @mixin CacheModelDecorator
 */
class CachedServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/cached.php' => config_path('cached.php'),
        ], 'config');

        $this->commands(CacheFollowCommand::class);

        $this->registerMacros();
    }

    public function registerMacros()
    {
        $factory = new DecoratorFactory;

        $decorate_macro = config('cached.macros.builder.decorate','decorate');

        Builder::macro($decorate_macro, function ($decorator = null) use ($factory) {
            return $decorator
                ? new $decorator($this->getModel())
                : $factory($this->getModel());
        });

        $find_macro = config('cached.macros.builder.cachedOrFail', 'cached');

        Builder::macro($find_macro, function ($id, $decorator = null) use ($factory) {
            return $decorator === true
                ? new $factory(get_class($this->getModel()), $id)
                : empty($decorator)
                    ? $factory(get_class($this->getModel()), $id)->getModel()
                    : new $decorator(get_class($this->getModel()), $id);
        });

        $find_or_fail_macro = config('cached.macros.builder.cachedOrFail', 'cachedOrFail');

        Builder::macro($find_or_fail_macro, function ($id, $decorator = null) use ($factory) {
            $decorated = $decorator === true || is_null($decorator)
                ? new $factory(get_class($this->getModel()), $id)
                : new $decorator(get_class($this->getModel()), $id);

            if (empty($model = $decorated->getModel())) {
                throw (new ModelNotFoundException($decorated->getModelClass(), $decorated->getModelId()));
            }

            return $decorator ? $decorated : $model;
        });
    }

    public function register()
    {

    }
}
