<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\RepositoryCollaborator;
use App\Models\Repository;
use App\Models\Role;

class RepositoryCollaboratorFactory extends Factory
{
    protected $model = RepositoryCollaborator::class;

    public function definition()
    {
        return [
            'repository_id' => Repository::factory(),
            'github_user_id' => $this->faker->unique()->randomNumber(6),
            'github_username' => $this->faker->unique()->userName(),
            'github_avatar_url' => $this->faker->imageUrl(100, 100, 'people'),
            'github_email' => $this->faker->email(),
            'permission_level' => $this->faker->randomElement(['read', 'write', 'admin']),
            'is_active' => true,
            'last_synced_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function withRole()
    {
        return $this->state([
            'vault_role_id' => Role::factory(),
        ]);
    }

    public function admin()
    {
        return $this->state([
            'permission_level' => 'admin',
        ]);
    }

    public function inactive()
    {
        return $this->state([
            'is_active' => false,
        ]);
    }
}