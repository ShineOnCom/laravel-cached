<?php

namespace More\Laravel\Cached\Providers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use More\Laravel\Cached\CacheDecorator;
use More\Laravel\Cached\Console\CacheFollowCommand;
use More\Laravel\Cached\Support\DecoratorFactory;

/**
 * Class CachedServiceProvider
 *
 * @mixin CacheDecorator
 */
class CachedServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/cached.php' => config_path('cached.php'),
        ], 'config');

        $this->commands(CacheFollowCommand::class);

        $this->registerCollectionMacros();

        $this->registerBuilderMacros();
    }

    /**
     * @return void
     */
    protected function registerCollectionMacros()
    {
        $factory = new DecoratorFactory;

        Collection::macro('decorate', function ($decorator = true) use ($factory) {
            return $this->map(function (Model $model) use ($decorator, $factory) {
                return $decorator === true
                    ? new $factory(get_class($model), $model->getKey())
                    : new $decorator(get_class($model), $model->getKey());
            });
        });

        Collection::macro('decorateTransformed', function ($decorator = true) use ($factory) {
            return $this->transform(function (Model $model) use ($decorator, $factory) {
                return $decorator === true
                    ? new $factory(get_class($model), $model->getKey())
                    : new $decorator(get_class($model), $model->getKey());
            });
        });
    }

    /**
     * @return void
     */
    protected function registerBuilderMacros()
    {
        $factory = new DecoratorFactory;

        $decorate_macro = config('cached.macros.builder.decorate','decorate');

        Builder::macro($decorate_macro, function ($decorator = null) use ($factory) {
            return $decorator
                ? new $decorator($this->getModel())
                : $factory($this->getModel());
        });

        $find_macro = config('cached.macros.builder.cached', 'cached');

        Builder::macro($find_macro, function ($id, $decorator = null) use ($factory, $find_macro) {
            if ($decorator === true) {
                return $factory(get_class($this->getModel()), $id);
            } elseif ($decorator) {
                return new $decorator(get_class($this->getModel()), $id);
            } else {
                return $factory(get_class($this->getModel()), $id)->getModel();
            }
        });

        $find_or_fail_macro = config('cached.macros.builder.cachedOrFail', 'cachedOrFail');

        Builder::macro($find_or_fail_macro, function ($id, $decorator = null) use ($factory, $find_macro) {
            $fetch = call_user_func_array([$this, $find_macro], [$id, $decorator]);

            if (is_null($fetch) || ($fetch && is_null($fetch->getModel()))) {
                throw (new ModelNotFoundException())->setModel(get_class($this->getModel()), $id);
            }

            return $fetch;
        });
    }

    public function register()
    {

    }
}
