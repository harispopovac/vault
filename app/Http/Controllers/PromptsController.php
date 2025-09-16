<?php

namespace App\Http\Controllers;

use App\Models\Prompt;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class PromptsController extends Controller
{
    /**
     * Display a listing of prompts for the authenticated user's organisation.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = User::with('organisations')->find(auth()->id());
            $organizationIds = $user->organisations->pluck('id');

            $prompts = Prompt::with(['organization', 'creator', 'template'])
                ->whereIn('organization_id', $organizationIds)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $prompts,
                'message' => 'Prompts retrieved successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch prompts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created prompt.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'field_definitions' => 'required|array',
                'created_from_template_id' => 'nullable|uuid|exists:prompt_templates,id',
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

            $prompt = Prompt::create([
                'name' => $request->name,
                'description' => $request->description,
                'field_definitions' => $request->field_definitions,
                'organization_id' => $organizationId,
                'created_from_template_id' => $request->created_from_template_id,
                'created_by' => auth()->id(),
            ]);

            $prompt->load(['organization', 'creator', 'template']);

            return response()->json([
                'success' => true,
                'data' => $prompt,
                'message' => 'Prompt created successfully',
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create prompt: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified prompt.
     */
    public function show(Prompt $prompt): JsonResponse
    {
        try {
            $user = User::with('organisations')->find(auth()->id());
            $organizationIds = $user->organisations->pluck('id');

            if (!$organizationIds->contains($prompt->organization_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied',
                ], 403);
            }

            $prompt->load(['organization', 'creator', 'template', 'triggers']);

            return response()->json([
                'success' => true,
                'data' => $prompt,
                'message' => 'Prompt retrieved successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Update the specified prompt.
     */
    public function update(Request $request, Prompt $prompt): JsonResponse
    {
        try {
            $user = User::with('organisations')->find(auth()->id());
            $organizationIds = $user->organisations->pluck('id');

            if (!$organizationIds->contains($prompt->organization_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'field_definitions' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $prompt->update([
                'name' => $request->name,
                'description' => $request->description,
                'field_definitions' => $request->field_definitions,
            ]);

            $prompt->load(['organization', 'creator', 'template']);

            return response()->json([
                'success' => true,
                'data' => $prompt,
                'message' => 'Prompt updated successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Remove the specified prompt.
     */
    public function destroy(Prompt $prompt): JsonResponse
    {
        try {
            $user = User::with('organisations')->find(auth()->id());
            $organizationIds = $user->organisations->pluck('id');

            if (!$organizationIds->contains($prompt->organization_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied',
                ], 403);
            }

            $prompt->delete();

            return response()->json([
                'success' => true,
                'message' => 'Prompt deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }
}
