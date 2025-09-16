<?php

namespace Sites\SitesModule;

use Illuminate\Support\ServiceProvider;
use Sites\SitesModule\Models\Site;
use Sites\SitesModule\Repositories\SiteRepository;

class SitesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the repository
        $this->app->bind(SiteRepository::class, function ($app) {
            return new SiteRepository($app->make(Site::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Publish config
        $this->publishes([
            __DIR__ . '/../config/sites.php' => config_path('sites.php'),
        ], 'config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'migrations');
    }
}
