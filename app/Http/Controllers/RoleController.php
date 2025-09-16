<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
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

            // For now, return all roles (in future, scope by organization)
            $roles = Role::withCount('collaborators')
                ->orderBy('name')
                ->get();

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
     * Store a newly created role.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:roles,name',
                'description' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $role = Role::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'data' => $role,
                'message' => 'Role created successfully',
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role): JsonResponse
    {
        try {
            $role->load('collaborators');
            $role->loadCount('collaborators');

            return response()->json([
                'success' => true,
                'data' => $role,
                'message' => 'Role retrieved successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch role: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
                'description' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $role->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'data' => $role->fresh(),
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
     * Remove the specified role.
     */
    public function destroy(Role $role): JsonResponse
    {
        try {
            // Check if role is assigned to any collaborators
            $collaboratorsCount = $role->collaborators()->count();

            if ($collaboratorsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Cannot delete role. It is assigned to {$collaboratorsCount} developer(s). Please reassign them first.",
                ], 409);
            }

            $roleName = $role->name;
            $role->delete();

            return response()->json([
                'success' => true,
                'message' => "Role '{$roleName}' deleted successfully",
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role: ' . $e->getMessage(),
            ], 500);
        }
    }
}