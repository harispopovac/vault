<?php

namespace App\Http\Controllers;

use App\Http\Requests\Repository\StoreRepositoryRequest;
use App\Http\Requests\Repository\UpdateRepositoryRequest;
use App\Models\Repository;
use App\Repositories\RepositoryRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class RepositoriesController extends Controller
{
    protected RepositoryRepository $repositoryRepository;

    public function __construct(RepositoryRepository $repositoryRepository)
    {
        $this->repositoryRepository = $repositoryRepository;
    }

    /**
     * Display a listing of repositories for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $repositories = $this->repositoryRepository->index(auth()->id());
            
            return response()->json([
                'success' => true,
                'data' => $repositories['data'] ?? [],
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

    /**
     * Store a newly created repository.
     */
    public function store(StoreRepositoryRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $repository = $this->repositoryRepository->store($validated, auth()->id());

            return response()->json([
                'success' => true,
                'data' => $repository['data'] ?? null,
                'message' => 'Repository linked successfully',
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to link repository: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified repository.
     */
    public function show(Repository $repository): JsonResponse
    {
        try {
            $repositoryData = $this->repositoryRepository->show($repository, auth()->id());

            return response()->json([
                'success' => true,
                'data' => $repositoryData['data'] ?? null,
                'message' => 'Repository retrieved successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Update the specified repository.
     */
    public function update(UpdateRepositoryRequest $request, Repository $repository): JsonResponse
    {
        try {
            $validated = $request->validated();
            $repositoryData = $this->repositoryRepository->update($repository, $validated, auth()->id());

            return response()->json([
                'success' => true,
                'data' => $repositoryData['data'] ?? null,
                'message' => 'Repository updated successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Remove the specified repository.
     */
    public function destroy(Repository $repository): JsonResponse
    {
        try {
            $this->repositoryRepository->destroy($repository, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Repository unlinked successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }
}