<?php

namespace FilesManagement\FilesManagementModule;

use Illuminate\Support\ServiceProvider;

class FilesManagementServiceProvider extends ServiceProvider
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
