<?php

namespace Address\AddressModule;

use Illuminate\Support\ServiceProvider;

class AddressServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    public function register()
    {
        // Register bindings or merge config if needed in future
    }
}


