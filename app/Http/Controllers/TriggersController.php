<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use App\Models\Trigger;
use App\Models\User;
use App\Services\GitHubService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Exception;

class TriggersController extends Controller
{
    protected GitHubService $gitHubService;

    public function __construct(GitHubService $gitHubService)
    {
        $this->gitHubService = $gitHubService;
    }
    /**
     * Display a listing of triggers for the authenticated user's organisation.
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

            $triggers = Trigger::with(['repository', 'prompt', 'organization', 'creator'])
                ->whereIn('organization_id', $organizationIds)
                ->when($request->repository_id, function($query, $repositoryId) {
                    return $query->where('repository_id', $repositoryId);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $triggers,
                'message' => 'Triggers retrieved successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch triggers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created trigger.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'repository_id' => 'required|uuid|exists:repositories,id',
                'prompt_id' => 'required|uuid|exists:prompts,id',
                'github_events' => 'required|array|min:1',
                'github_events.*' => 'string|in:push,pull_request,issues,release,create,delete',
                'event_filters' => 'nullable|array',
                'target_type' => 'required|in:all,specific_users,roles',
                'target_users' => 'nullable|array',
                'target_users.*' => 'integer|exists:users,id',
                'target_roles' => 'nullable|array',
                'target_roles.*' => 'integer|exists:roles,id',
                'delivery_delay_minutes' => 'integer|min:0|max:1440',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::with('organisations')->find(auth()->id());
            $organizationId = $user->organisations->first()->id;

            $trigger = Trigger::create([
                'name' => $request->name,
                'description' => $request->description,
                'repository_id' => $request->repository_id,
                'prompt_id' => $request->prompt_id,
                'github_events' => $request->github_events,
                'event_filters' => $request->event_filters ?? [],
                'target_type' => $request->target_type,
                'target_users' => $request->target_users ?? [],
                'target_roles' => $request->target_roles ?? [],
                'webhook_secret' => Str::random(40),
                'delivery_delay_minutes' => $request->delivery_delay_minutes ?? 0,
                'organization_id' => $organizationId,
                'created_by' => auth()->id(),
            ]);

            // Create GitHub webhook
            $repository = Repository::find($request->repository_id);
            $webhookData = $this->gitHubService->createWebhook($repository, $trigger);
            
            if ($webhookData) {
                $trigger->update([
                    'github_webhook_id' => $webhookData['id'],
                    'webhook_active' => true,
                ]);
            }

            $trigger->load(['repository', 'prompt', 'organization', 'creator']);

            return response()->json([
                'success' => true,
                'data' => $trigger,
                'message' => 'Trigger created successfully',
                'webhook_created' => $webhookData !== null,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create trigger: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified trigger.
     */
    public function show(Trigger $trigger): JsonResponse
    {
        try {
            $user = User::with('organisations')->find(auth()->id());
            $organizationIds = $user->organisations->pluck('id');

            if (!$organizationIds->contains($trigger->organization_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied',
                ], 403);
            }

            $trigger->load(['repository', 'prompt', 'organization', 'creator', 'deliveries.targetUser']);

            return response()->json([
                'success' => true,
                'data' => $trigger,
                'message' => 'Trigger retrieved successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Update the specified trigger.
     */
    public function update(Request $request, Trigger $trigger): JsonResponse
    {
        try {
            $user = User::with('organisations')->find(auth()->id());
            $organizationIds = $user->organisations->pluck('id');

            if (!$organizationIds->contains($trigger->organization_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'github_events' => 'required|array|min:1',
                'github_events.*' => 'string|in:push,pull_request,issues,release,create,delete',
                'event_filters' => 'nullable|array',
                'target_type' => 'required|in:all,specific_users,roles',
                'target_users' => 'nullable|array',
                'target_users.*' => 'integer|exists:users,id',
                'target_roles' => 'nullable|array',
                'target_roles.*' => 'integer|exists:roles,id',
                'delivery_delay_minutes' => 'integer|min:0|max:1440',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $trigger->update([
                'name' => $request->name,
                'description' => $request->description,
                'github_events' => $request->github_events,
                'event_filters' => $request->event_filters ?? [],
                'target_type' => $request->target_type,
                'target_users' => $request->target_users ?? [],
                'target_roles' => $request->target_roles ?? [],
                'delivery_delay_minutes' => $request->delivery_delay_minutes ?? 0,
                'is_active' => $request->has('is_active') ? $request->is_active : $trigger->is_active,
            ]);

            $trigger->load(['repository', 'prompt', 'organization', 'creator']);

            return response()->json([
                'success' => true,
                'data' => $trigger,
                'message' => 'Trigger updated successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Remove the specified trigger.
     */
    public function destroy(Trigger $trigger): JsonResponse
    {
        try {
            $user = User::with('organisations')->find(auth()->id());
            $organizationIds = $user->organisations->pluck('id');

            if (!$organizationIds->contains($trigger->organization_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied',
                ], 403);
            }

            // Delete GitHub webhook before deleting trigger
            if ($trigger->github_webhook_id) {
                $repository = Repository::find($trigger->repository_id);
                $this->gitHubService->deleteWebhook($repository, $trigger);
            }

            $trigger->delete();

            return response()->json([
                'success' => true,
                'message' => 'Trigger deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Toggle the active status of a trigger.
     */
    public function toggle(Trigger $trigger): JsonResponse
    {
        try {
            $user = User::with('organisations')->find(auth()->id());
            $organizationIds = $user->organisations->pluck('id');

            if (!$organizationIds->contains($trigger->organization_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied',
                ], 403);
            }

            $trigger->update(['is_active' => !$trigger->is_active]);

            return response()->json([
                'success' => true,
                'data' => ['is_active' => $trigger->is_active],
                'message' => $trigger->is_active ? 'Trigger activated' : 'Trigger deactivated',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
