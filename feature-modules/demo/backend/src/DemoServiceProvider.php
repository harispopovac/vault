<?php

namespace Demo\DemoModule;

use Illuminate\Support\ServiceProvider;

class DemoServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }

    public function register()
    {
        //
    }
} 
