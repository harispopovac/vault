<?php

namespace Colors\ColorsModule;

use Illuminate\Support\ServiceProvider;

class ColorsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Publish config if needed
        $this->publishes([
            __DIR__.'/../config/colors.php' => config_path('colors.php'),
        ], 'config');
    }

    public function register()
    {
        // Register any bindings or configurations
        $this->mergeConfigFrom(__DIR__.'/../config/colors.php', 'colors');
    }
}
