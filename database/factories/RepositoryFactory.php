<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Repository;
use Illuminate\Support\Str;

class RepositoryFactory extends Factory
{
    protected $model = Repository::class;

    public function definition()
    {
        $repoName = $this->faker->word() . '-' . $this->faker->word();
        return [
            'github_id' => $this->faker->unique()->randomNumber(8),
            'name' => $repoName,
            'owner_id' => $this->faker->randomNumber(6),
            'webhook_secret' => Str::random(32),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function withWebhookSecret($secret)
    {
        return $this->state([
            'webhook_secret' => $secret,
        ]);
    }

    public function vault()
    {
        return $this->state([
            'github_id' => 123456789,
            'name' => 'harispopovac/vault',
            'owner_id' => 12345,
        ]);
    }

    public function testRepository()
    {
        return $this->state([
            'github_id' => 987654321,
            'name' => 'test-org/test-repo',
            'owner_id' => 67890,
            'webhook_secret' => 'test-webhook-secret',
        ]);
    }
}