<?php

namespace StaffRoster\StaffRosterModule;

use Illuminate\Support\ServiceProvider;

class StaffRosterServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Publish config if needed
        // $this->publishes([
        //     __DIR__.'/../config/staff-roster.php' => config_path('staff-roster.php'),
        // ], 'config');
    }

    public function register()
    {
        // Register any bindings or configurations
        $this->mergeConfigFrom(__DIR__.'/../config/staff-roster.php', 'staff-roster');
    }
}
