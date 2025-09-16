<?php

namespace StaffRoster\StaffRosterModule;

use Illuminate\Support\ServiceProvider;

class StaffRosterServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Publish config
        $this->publishes([
            __DIR__.'/../config/roster.php' => config_path('roster.php'),
        ], 'config');
    }

    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(__DIR__.'/../config/roster.php', 'roster');
    }
}
