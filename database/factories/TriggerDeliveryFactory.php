<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\TriggerDelivery;
use App\Models\Trigger;
use Illuminate\Support\Str;

class TriggerDeliveryFactory extends Factory
{
    protected $model = TriggerDelivery::class;

    public function definition()
    {
        $token = Str::random(32);
        return [
            'trigger_id' => Trigger::factory(),
            'target_user_id' => $this->faker->randomNumber(6),
            'github_event_type' => $this->faker->randomElement(['push', 'pull_request', 'create']),
            'github_payload' => $this->generateGitHubPayload(),
            'github_delivery_id' => 'test-delivery-' . time() . '-' . $this->faker->randomNumber(4),
            'status' => 'pending',
            'prompt_token' => $token,
            'delivery_url' => url("/prompt/{$token}"),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function responded()
    {
        return $this->state([
            'status' => 'responded',
            'delivered_at' => now()->subMinutes(30),
            'responded_at' => now()->subMinutes(20),
            'opened_at' => now()->subMinutes(25),
            'response_data' => [
                'knowledge_context' => 'Test knowledge context',
                'technical_details' => 'Test technical details',
                'lessons_learned' => 'Test lessons learned',
                'future_considerations' => 'Test future considerations'
            ]
        ]);
    }

    public function delivered()
    {
        return $this->state([
            'status' => 'delivered',
            'delivered_at' => now()->subMinutes(10),
            'opened_at' => now()->subMinutes(5),
        ]);
    }

    public function expired()
    {
        return $this->state([
            'status' => 'expired',
            'delivered_at' => now()->subHours(2),
        ]);
    }

    public function withPushPayload()
    {
        return $this->state([
            'github_event_type' => 'push',
            'github_payload' => [
                'repository' => [
                    'id' => $this->faker->randomNumber(8),
                    'name' => $this->faker->word(),
                    'full_name' => $this->faker->userName() . '/' . $this->faker->word()
                ],
                'ref' => 'refs/heads/main',
                'commits' => [
                    [
                        'id' => $this->faker->sha1(),
                        'message' => $this->faker->sentence(),
                        'author' => [
                            'name' => $this->faker->name(),
                            'email' => $this->faker->email()
                        ]
                    ]
                ],
                'sender' => [
                    'id' => $this->faker->randomNumber(6),
                    'login' => $this->faker->userName()
                ]
            ]
        ]);
    }

    public function withPullRequestPayload($merged = true)
    {
        return $this->state([
            'github_event_type' => 'pull_request',
            'github_payload' => [
                'action' => 'closed',
                'repository' => [
                    'id' => $this->faker->randomNumber(8),
                    'name' => $this->faker->word(),
                    'full_name' => $this->faker->userName() . '/' . $this->faker->word()
                ],
                'pull_request' => [
                    'id' => $this->faker->randomNumber(5),
                    'number' => $this->faker->numberBetween(1, 1000),
                    'title' => $this->faker->sentence(),
                    'merged' => $merged,
                    'merge_commit_sha' => $merged ? $this->faker->sha1() : null
                ],
                'sender' => [
                    'id' => $this->faker->randomNumber(6),
                    'login' => $this->faker->userName()
                ]
            ]
        ]);
    }

    private function generateGitHubPayload()
    {
        return [
            'repository' => [
                'id' => $this->faker->randomNumber(8),
                'name' => $this->faker->word(),
                'full_name' => $this->faker->userName() . '/' . $this->faker->word()
            ],
            'sender' => [
                'id' => $this->faker->randomNumber(6),
                'login' => $this->faker->userName()
            ],
            'action' => 'closed',
            'pull_request' => [
                'number' => $this->faker->numberBetween(1, 1000),
                'title' => $this->faker->sentence(),
                'merged' => true
            ]
        ];
    }
}