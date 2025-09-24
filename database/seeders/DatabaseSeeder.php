<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(UserSeeder::class);

        // Run testing seeders in test environment
        if (app()->environment('testing')) {
            $this->call([
                WebhookTestingSeeder::class,
                BrowserTabTestingSeeder::class,
            ]);
        }
    }
}
