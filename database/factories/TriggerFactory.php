<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Trigger;
use App\Models\Repository;
use App\Models\Prompt;
use App\Models\Organisation;
use App\Models\User;
use Illuminate\Support\Str;

class TriggerFactory extends Factory
{
    protected $model = Trigger::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(2, true) . ' Trigger',
            'description' => $this->faker->sentence(),
            'repository_id' => Repository::factory(),
            'prompt_id' => Prompt::factory(),
            'github_events' => ['push'],
            'event_filters' => [],
            'target_type' => 'all',
            'target_users' => [],
            'target_roles' => [],
            'webhook_secret' => Str::random(32),
            'webhook_active' => true,
            'is_active' => true,
            'delivery_delay_minutes' => 0,
            'organisation_id' => Organisation::factory(),
            'created_by' => User::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function forPushEvents()
    {
        return $this->state([
            'name' => 'Push Event Trigger',
            'github_events' => ['push'],
            'event_filters' => [
                'push' => [
                    'branches' => ['main', 'develop']
                ]
            ]
        ]);
    }

    public function forPullRequests()
    {
        return $this->state([
            'name' => 'PR Merge Trigger',
            'github_events' => ['pull_request'],
            'event_filters' => [
                'pull_request' => [
                    'actions' => ['closed'],
                    'merged_only' => true
                ]
            ]
        ]);
    }

    public function targetingSpecificUsers($userIds = [])
    {
        return $this->state([
            'target_type' => 'specific_users',
            'target_users' => $userIds ?: [12345, 67890],
        ]);
    }

    public function targetingRoles($roleIds = [])
    {
        return $this->state([
            'target_type' => 'roles',
            'target_roles' => $roleIds ?: [1, 2],
        ]);
    }

    public function inactive()
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    public function withDelay($minutes = 5)
    {
        return $this->state([
            'delivery_delay_minutes' => $minutes,
        ]);
    }
}