<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Account;
use App\Models\TimeEntry;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Generate users
        User::factory()->count(10)->create()->each(function ($user) {
            // Create a default account for the user
            $account = Account::factory()->create([
                'user_id' => $user->id,
                'default_account' => true,
            ]);

            $timeEntries = [];

            // Generate 99 completed time entries (non-overlapping)
            for ($i = 0; $i < 99; $i++) {
                $startTime = $i === 0
                    ? now()->subDays(10)->addHours($i * 8) // Start from 10 days ago
                    : end($timeEntries)['end_time']->addMinutes(rand(15, 60)); // Ensure no overlap

                $endTime = $startTime->copy()->addHours(rand(1, 8)); // Random duration between 1-8 hours

                $timeEntries[] = TimeEntry::factory()->create([
                    'user_id' => $user->id,
                    'user_account_id' => $account->id,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ]);
            }

            // Create 1 active time entry (end_time = NULL)
            $startTime = now()->subHours(rand(1, 5)); // Most recent start time

            TimeEntry::factory()->create([
                'user_id' => $user->id,
                'user_account_id' => $account->id,
                'start_time' => $startTime,
                'end_time' => null, // Active entry
            ]);
        });
    }
}
