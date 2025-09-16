<?php

namespace ShiftLog\ShiftLogModule;

use Illuminate\Support\ServiceProvider;
use ShiftLog\ShiftLogModule\Repositories\StaffRosterLogRepository;

class ShiftLogServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/shift-log.php' => config_path('shift-log.php'),
        ], 'shift-log-config');
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        // Merge configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/shift-log.php', 'shift-log');

        // Register repository
        $this->app->bind(StaffRosterLogRepository::class, function ($app) {
            return new StaffRosterLogRepository(
                $app->make(\ShiftLog\ShiftLogModule\Models\StaffRosterLog::class),
                $app->make(\StaffRoster\StaffRosterModule\Models\StaffRoster::class)
            );
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            StaffRosterLogRepository::class,
        ];
    }
}
