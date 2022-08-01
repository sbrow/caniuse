<?php

namespace Sbrow\Caniuse;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class CaniuseServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'sbrow');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'sbrow');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        // Publishing is only necessary when using the CLI.
        if ($this->app->runningInConsole()) {
            $this->bootForConsole();
        }

        Str::macro('version', function(string $str) {
            $str = Str::replace('_', '.', $str);
            $str = Str::replace('-', '.', $str);
            $str = Str::replace('+', '.', $str);

            return $str;
        });

        Stringable::macro('version', function() {
            return $this
                ->replace('_', '.')
                ->replace('-', '.')
                ->replace('+', '.');
        });
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register(): void
    {
//        $this->mergeConfigFrom(__DIR__.'/../config/caniuse.php', 'caniuse');

        // Register the service the package provides.
        $this->app->singleton('caniuse', function ($app) {
            return new Caniuse;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['caniuse'];
    }

    /**
     * Console-specific booting.
     *
     * @return void
     */
    protected function bootForConsole(): void
    {
        // Publishing the configuration file.
        $this->publishes([
            __DIR__.'/../config/caniuse.php' => config_path('caniuse.php'),
        ], 'caniuse.config');

        // Publishing the views.
        /*$this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/sbrow'),
        ], 'caniuse.views');*/

        // Publishing assets.
        /*$this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/sbrow'),
        ], 'caniuse.views');*/

        // Publishing the translation files.
        /*$this->publishes([
            __DIR__.'/../resources/lang' => resource_path('lang/vendor/sbrow'),
        ], 'caniuse.views');*/

        // Registering package commands.
        // $this->commands([]);
    }
}
