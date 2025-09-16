<?php

namespace App\Repositories;

use App\Models\Repository;
use App\Models\Organisation;
use App\Transformers\Repository\RepositoryTransformer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class RepositoryRepository
{
    protected Repository $repository;

    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    public function index($userId = null): ?array
    {
        $query = $this->repository->newQuery();

        // Include relationships
        $query->with(['organisations', 'users']);

        // Filter by user access - repositories the user has access to
        if ($userId) {
            $query->where(function ($q) use ($userId) {
                // Direct repository access
                $q->whereHas('users', function ($userQuery) use ($userId) {
                    $userQuery->where('user_id', $userId);
                })
                // Or access through organisation
                ->orWhereHas('organisations.users', function ($orgQuery) use ($userId) {
                    $orgQuery->where('user_id', $userId);
                });
            });
        }

        $repositories = $query->orderBy('name')->get();

        return fractal($repositories, new RepositoryTransformer())->toArray();
    }

    public function store($data, $userId = null): ?array
    {
        DB::beginTransaction();

        try {
            // Parse GitHub URL
            $repoInfo = $this->parseGitHubUrl($data['url']);
            
            if (!$repoInfo) {
                throw new Exception('Invalid GitHub repository URL format');
            }

            // Check if repository already exists
            $existingRepo = $this->repository->where('name', $repoInfo['fullName'])->first();
            if ($existingRepo) {
                throw new Exception('Repository is already linked');
            }

            // Create repository
            $repository = $this->repository->create([
                'github_id' => rand(100000, 999999), // Temporary - should be from GitHub API
                'name' => $repoInfo['fullName'],
                'owner_id' => $userId,
                'webhook_secret' => Str::random(32),
            ]);

            // Get user's organisation (use first organisation or create default)
            $organisation = Organisation::whereHas('users', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->first();

            if (!$organisation) {
                // Get the default organisation
                $organisation = Organisation::find(1);
            }

            if ($organisation) {
                // Link repository to organisation
                $repository->organisations()->attach($organisation->id);
                
                // Give user admin access to the repository
                $repository->users()->attach($userId, [
                    'is_admin' => true,
                    'role_id' => null,
                ]);
            }

            DB::commit();

            return fractal($repository->load(['organisations', 'users']), new RepositoryTransformer())->toArray();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function show(Repository $repository, $userId = null): ?array
    {
        // Check if user has access
        if ($userId && !$this->userHasAccess($repository, $userId)) {
            throw new Exception('Access denied to this repository');
        }

        $repository->load(['organisations', 'users', 'knowledgeEntries']);
        return fractal($repository, new RepositoryTransformer())->toArray();
    }

    public function update(Repository $repository, $data, $userId = null): ?array
    {
        // Check if user has admin access
        if ($userId && !$this->userHasAccess($repository, $userId, true)) {
            throw new Exception('Admin access required to modify this repository');
        }

        DB::beginTransaction();

        try {
            $repository->update($data);
            DB::commit();

            return fractal($repository->load(['organisations', 'users']), new RepositoryTransformer())->toArray();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function destroy(Repository $repository, $userId = null): bool
    {
        // Check if user has admin access
        if ($userId && !$this->userHasAccess($repository, $userId, true)) {
            throw new Exception('Admin access required to delete this repository');
        }

        DB::beginTransaction();

        try {
            // Remove repository relationships
            $repository->organisations()->detach();
            $repository->users()->detach();
            
            // Delete the repository (knowledge entries will be cascade deleted)
            $repository->delete();

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Parse GitHub URL and extract owner and repository name
     */
    private function parseGitHubUrl(string $url): ?array
    {
        try {
            $parsedUrl = parse_url($url);
            
            if (!$parsedUrl || !isset($parsedUrl['host']) || !str_contains($parsedUrl['host'], 'github.com')) {
                return null;
            }

            $pathSegments = explode('/', trim($parsedUrl['path'], '/'));
            
            if (count($pathSegments) < 2) {
                return null;
            }

            $owner = $pathSegments[0];
            $repoName = str_replace('.git', '', $pathSegments[1]);
            
            return [
                'owner' => $owner,
                'name' => $repoName,
                'fullName' => "{$owner}/{$repoName}",
            ];
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Check if user has access to repository
     */
    private function userHasAccess(Repository $repository, $userId, bool $requireAdmin = false): bool
    {
        // Check direct repository access
        $repositoryUser = $repository->users()->where('user_id', $userId)->first();
        if ($repositoryUser) {
            return $requireAdmin ? $repositoryUser->pivot->is_admin : true;
        }

        // Check organization access (non-admin for now)
        $hasOrgAccess = $repository->organisations()
            ->whereHas('users', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->exists();

        return $hasOrgAccess && !$requireAdmin;
    }
}