<?php

namespace App\Services;

use App\Models\Repository;
use App\Models\RepositoryCollaborator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GitHubCollaboratorService
{
    private string $baseUrl = 'https://api.github.com';
    private ?string $token = null;

    public function __construct(?string $token = null)
    {
        $this->token = $token ?: config('services.github.token');
    }

    /**
     * Fetch collaborators from GitHub API for a repository.
     */
    public function fetchRepositoryCollaborators(Repository $repository): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
            ])->get("{$this->baseUrl}/repos/{$repository->owner_id}/{$repository->name}/collaborators");

            if ($response->successful()) {
                return collect($response->json())->map(function ($collaborator) {
                    return [
                        'github_user_id' => $collaborator['id'],
                        'github_username' => $collaborator['login'],
                        'github_avatar_url' => $collaborator['avatar_url'],
                        'github_email' => $collaborator['email'] ?? null,
                        'permission_level' => $this->mapPermissionLevel($collaborator['permissions'] ?? [])
                    ];
                })->toArray();
            }

            throw new Exception("Failed to fetch collaborators: HTTP {$response->status()}");
        } catch (Exception $e) {
            Log::error("Error fetching collaborators for repository {$repository->name}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync collaborators for a specific repository.
     */
    public function syncRepositoryCollaborators(Repository $repository): array
    {
        try {
            // Check rate limit before making API calls
            $this->checkRateLimit();

            $githubCollaborators = $this->fetchRepositoryCollaborators($repository);
            
            $stats = [
                'total_fetched' => count($githubCollaborators),
                'created' => 0,
                'updated' => 0,
                'deactivated' => 0
            ];

            // Mark all existing collaborators as inactive initially
            $repository->collaborators()->update(['is_active' => false]);

            foreach ($githubCollaborators as $collaboratorData) {
                $existingCollaborator = RepositoryCollaborator::where('repository_id', $repository->id)
                    ->where('github_user_id', $collaboratorData['github_user_id'])
                    ->first();

                if ($existingCollaborator) {
                    // Update existing collaborator
                    $existingCollaborator->update([
                        'github_username' => $collaboratorData['github_username'],
                        'github_avatar_url' => $collaboratorData['github_avatar_url'],
                        'github_email' => $collaboratorData['github_email'],
                        'permission_level' => $collaboratorData['permission_level'],
                        'is_active' => true,
                        'last_synced_at' => now(),
                    ]);
                    $stats['updated']++;
                } else {
                    // Create new collaborator
                    RepositoryCollaborator::create([
                        'repository_id' => $repository->id,
                        'github_user_id' => $collaboratorData['github_user_id'],
                        'github_username' => $collaboratorData['github_username'],
                        'github_avatar_url' => $collaboratorData['github_avatar_url'],
                        'github_email' => $collaboratorData['github_email'],
                        'permission_level' => $collaboratorData['permission_level'],
                        'is_active' => true,
                        'last_synced_at' => now(),
                    ]);
                    $stats['created']++;
                }
            }

            // Count deactivated collaborators
            $stats['deactivated'] = $repository->collaborators()->where('is_active', false)->count();

            Log::info("Synced collaborators for repository {$repository->name}", $stats);

            return $stats;
        } catch (Exception $e) {
            Log::error("Error syncing collaborators for repository {$repository->name}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync collaborators for multiple repositories with rate limiting.
     */
    public function syncMultipleRepositories(array $repositories): array
    {
        $results = [];
        
        foreach ($repositories as $repository) {
            try {
                // Check rate limit before each repository
                $this->checkRateLimit();
                
                $stats = $this->syncRepositoryCollaborators($repository);
                $results[$repository->id] = [
                    'success' => true,
                    'stats' => $stats,
                    'repository_name' => $repository->name
                ];
                
                // Small delay between requests to be respectful to GitHub API
                usleep(100000); // 0.1 seconds
                
            } catch (Exception $e) {
                $results[$repository->id] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'repository_name' => $repository->name
                ];
                
                // If we hit a rate limit, break the loop
                if (str_contains($e->getMessage(), 'rate limit')) {
                    Log::warning("Rate limit hit during bulk sync, stopping at repository: {$repository->name}");
                    break;
                }
            }
        }
        
        return $results;
    }

    /**
     * Check GitHub API rate limit.
     */
    public function checkRateLimit(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
            ])->get("{$this->baseUrl}/rate_limit");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'remaining' => $data['rate']['remaining'],
                    'reset_time' => $data['rate']['reset'],
                    'limit' => $data['rate']['limit'],
                ];
            }

            throw new Exception("Failed to check rate limit: HTTP {$response->status()}");
        } catch (Exception $e) {
            Log::error("Error checking GitHub rate limit: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Map GitHub permissions to our permission levels.
     */
    private function mapPermissionLevel(array $permissions): string
    {
        if (isset($permissions['admin']) && $permissions['admin']) {
            return 'admin';
        }
        
        if (isset($permissions['push']) && $permissions['push']) {
            return 'write';
        }
        
        return 'read';
    }

    /**
     * Get repository information from GitHub.
     */
    public function getRepositoryInfo(string $owner, string $repo): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->token,
                'Accept' => 'application/vnd.github.v3+json',
            ])->get("{$this->baseUrl}/repos/{$owner}/{$repo}");

            if ($response->successful()) {
                return $response->json();
            }

            throw new Exception("Failed to get repository info: HTTP {$response->status()}");
        } catch (Exception $e) {
            Log::error("Error fetching repository info for {$owner}/{$repo}: " . $e->getMessage());
            throw $e;
        }
    }
}