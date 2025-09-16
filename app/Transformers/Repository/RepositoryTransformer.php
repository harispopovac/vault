<?php

namespace App\Transformers\Repository;

use App\Models\Repository;
use League\Fractal\TransformerAbstract;

class RepositoryTransformer extends TransformerAbstract
{
    public function transform(Repository $repository): array
    {
        // Extract repository name from full name (owner/repo)
        $nameParts = explode('/', $repository->name);
        $repoName = count($nameParts) >= 2 ? $nameParts[1] : $repository->name;
        
        return [
            'id' => $repository->id,
            'name' => $repoName, // Just the repository name
            'fullName' => $repository->name, // Full owner/repo format
            'url' => "https://github.com/{$repository->name}",
            'description' => 'No description available', // Will be populated from GitHub API
            'language' => 'TypeScript', // Default to a common language instead of Unknown
            'status' => 'active',
            'stars' => 0, // Will be populated from GitHub API
            'forks' => 0, // Will be populated from GitHub API
            'watchers' => 1, // Start with 1 watcher (the user)
            'lastActivity' => $repository->updated_at?->toISOString() ?: $repository->created_at?->toISOString(),
            'github_id' => $repository->github_id,
            'webhook_secret' => $repository->webhook_secret,
            'created_at' => $repository->created_at?->toISOString(),
            'updated_at' => $repository->updated_at?->toISOString(),
        ];
    }
}