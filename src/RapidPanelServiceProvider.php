<?php

namespace Rapide\RapidPanel;

use Illuminate\Support\ServiceProvider;

class RapidPanelServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/rapidpanel.php' => config_path('rapidpanel.php')
        ], 'config');

    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Rapide\RapidPanel\RapidPanelClient'];
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('RapidPanel', function ($config) {
            return new \Rapide\RapidPanel\RapidPanelClient($config);
        });
    }


}
