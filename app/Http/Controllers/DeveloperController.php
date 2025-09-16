<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use App\Models\RepositoryCollaborator;
use App\Models\Role;
use App\Models\User;
use App\Services\GitHubCollaboratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class DeveloperController extends Controller
{
    protected GitHubCollaboratorService $githubService;

    public function __construct(GitHubCollaboratorService $githubService)
    {
        $this->githubService = $githubService;
    }

    /**
     * Display a listing of developers (repository collaborators).
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }

            $user = User::with('organisations')->find($userId);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $organizationIds = $user->organisations->pluck('id');
            
            if ($organizationIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No organizations found for user',
                ]);
            }

            // Get repositories for user's organizations
            $repositoryIds = Repository::whereHas('organisations', function ($query) use ($organizationIds) {
                $query->whereIn('organisations.id', $organizationIds);
            })->pluck('id');

            // Build query for collaborators
            $query = RepositoryCollaborator::with(['repository', 'role'])
                ->whereIn('repository_id', $repositoryIds)
                ->active();

            // Apply filters
            if ($request->repository_id) {
                $query->where('repository_id', $request->repository_id);
            }

            if ($request->role_id) {
                $query->where('vault_role_id', $request->role_id);
            }

            if ($request->permission_level) {
                $query->where('permission_level', $request->permission_level);
            }

            if ($request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('github_username', 'ILIKE', '%' . $request->search . '%')
                      ->orWhere('github_email', 'ILIKE', '%' . $request->search . '%');
                });
            }

            $collaborators = $query->orderBy('github_username')->get();

            // Group collaborators by github_user_id to show all repositories per user
            $groupedCollaborators = $collaborators->groupBy('github_user_id')->map(function ($group) {
                $first = $group->first();
                return [
                    'id' => $first->id,
                    'github_user_id' => $first->github_user_id,
                    'github_username' => $first->github_username,
                    'github_avatar_url' => $first->github_avatar_url,
                    'github_email' => $first->github_email,
                    'vault_role_id' => $first->vault_role_id,
                    'role' => $first->role,
                    'repositories' => $group->map(function ($collab) {
                        return [
                            'id' => $collab->repository_id,
                            'name' => $collab->repository->name,
                            'permission_level' => $collab->permission_level,
                        ];
                    })->toArray(),
                    'last_synced_at' => $first->last_synced_at,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $groupedCollaborators,
                'message' => 'Developers retrieved successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch developers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync collaborators from GitHub for one or all repositories.
     */
    public function syncCollaborators(Request $request, ?Repository $repository = null): JsonResponse
    {
        try {
            $userId = auth()->id();
            $user = User::with('organisations')->find($userId);
            $organizationIds = $user->organisations->pluck('id');

            if ($repository) {
                // Check if user has access to this repository
                if (!$repository->organisations()->whereIn('organisations.id', $organizationIds)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Access denied to this repository',
                    ], 403);
                }

                $stats = $this->githubService->syncRepositoryCollaborators($repository);
                
                return response()->json([
                    'success' => true,
                    'data' => $stats,
                    'message' => "Collaborators synced successfully for {$repository->name}",
                ]);
            } else {
                // Sync all repositories in user's organizations
                $repositories = Repository::whereHas('organisations', function ($query) use ($organizationIds) {
                    $query->whereIn('organisations.id', $organizationIds);
                })->get();

                $results = $this->githubService->syncMultipleRepositories($repositories->toArray());
                
                return response()->json([
                    'success' => true,
                    'data' => $results,
                    'message' => 'Collaborators sync completed for all repositories',
                ]);
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync collaborators: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update role assignment for a collaborator.
     */
    public function updateRole(Request $request, RepositoryCollaborator $collaborator): JsonResponse
    {
        try {
            $userId = auth()->id();
            $user = User::with('organisations')->find($userId);
            $organizationIds = $user->organisations->pluck('id');

            // Check if user has access to this collaborator's repository
            if (!$collaborator->repository->organisations()->whereIn('organisations.id', $organizationIds)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'role_id' => 'nullable|integer|exists:roles,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update role for all collaborator records with same github_user_id
            // This ensures consistent role assignment across all repositories
            RepositoryCollaborator::where('github_user_id', $collaborator->github_user_id)
                ->whereHas('repository.organisations', function ($query) use ($organizationIds) {
                    $query->whereIn('organisations.id', $organizationIds);
                })
                ->update(['vault_role_id' => $request->role_id]);

            $collaborator->refresh();
            $collaborator->load('role');

            return response()->json([
                'success' => true,
                'data' => $collaborator,
                'message' => 'Role updated successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available roles for role assignment.
     */
    public function getRoles(): JsonResponse
    {
        try {
            $roles = Role::orderBy('name')->get();
            
            return response()->json([
                'success' => true,
                'data' => $roles,
                'message' => 'Roles retrieved successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch roles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get repositories for filtering.
     */
    public function getRepositories(): JsonResponse
    {
        try {
            $userId = auth()->id();
            $user = User::with('organisations')->find($userId);
            $organizationIds = $user->organisations->pluck('id');

            $repositories = Repository::whereHas('organisations', function ($query) use ($organizationIds) {
                $query->whereIn('organisations.id', $organizationIds);
            })->orderBy('name')->get();

            return response()->json([
                'success' => true,
                'data' => $repositories,
                'message' => 'Repositories retrieved successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch repositories',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}