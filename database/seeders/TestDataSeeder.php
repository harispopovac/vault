<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    /**
     * Seed the database with comprehensive testing data.
     * Run with: php artisan db:seed --class=TestDataSeeder
     */
    public function run(): void
    {
        $this->call([
            WebhookTestingSeeder::class,
            BrowserTabTestingSeeder::class,
        ]);

        $this->command->info('✅ Test data seeded successfully!');
        $this->command->info('');
        $this->command->info('Test data includes:');
        $this->command->info('- Webhook testing repository with secret: test-webhook-secret-123');
        $this->command->info('- Browser tab testing repository with secret: browser-tab-secret');
        $this->command->info('- Multiple collaborators and triggers for testing');
        $this->command->info('- Deliveries in various states (pending, delivered, responded, expired)');
        $this->command->info('');
        $this->command->info('You can now run tests with: php artisan test');
    }
}