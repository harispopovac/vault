<?php

namespace App\Http\Controllers;

use App\Http\Requests\PromptTemplate\StorePromptTemplateRequest;
use App\Http\Requests\PromptTemplate\UpdatePromptTemplateRequest;
use App\Models\PromptTemplate;
use App\Repositories\PromptTemplateRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class PromptTemplatesController extends Controller
{
    protected PromptTemplateRepository $promptTemplateRepository;

    public function __construct(PromptTemplateRepository $promptTemplateRepository)
    {
        $this->promptTemplateRepository = $promptTemplateRepository;
    }

    /**
     * Display a listing of prompt templates for the authenticated user's organisation.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $templates = $this->promptTemplateRepository->index(auth()->id());
            
            return response()->json([
                'success' => true,
                'data' => $templates['data'] ?? [],
                'message' => 'Prompt templates retrieved successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch prompt templates',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created prompt template.
     */
    public function store(StorePromptTemplateRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $template = $this->promptTemplateRepository->store($validated, auth()->id());

            return response()->json([
                'success' => true,
                'data' => $template['data'] ?? null,
                'message' => 'Prompt template created successfully',
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create prompt template: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified prompt template.
     */
    public function show(PromptTemplate $promptTemplate): JsonResponse
    {
        try {
            $templateData = $this->promptTemplateRepository->show($promptTemplate, auth()->id());

            return response()->json([
                'success' => true,
                'data' => $templateData['data'] ?? null,
                'message' => 'Prompt template retrieved successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Update the specified prompt template.
     */
    public function update(UpdatePromptTemplateRequest $request, PromptTemplate $promptTemplate): JsonResponse
    {
        try {
            $validated = $request->validated();
            $templateData = $this->promptTemplateRepository->update($promptTemplate, $validated, auth()->id());

            return response()->json([
                'success' => true,
                'data' => $templateData['data'] ?? null,
                'message' => 'Prompt template updated successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Remove the specified prompt template.
     */
    public function destroy(PromptTemplate $promptTemplate): JsonResponse
    {
        try {
            $this->promptTemplateRepository->destroy($promptTemplate, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Prompt template deleted successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    /**
     * Increment usage count for a template when it's used.
     */
    public function use(PromptTemplate $promptTemplate): JsonResponse
    {
        try {
            $this->promptTemplateRepository->incrementUsage($promptTemplate, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Template usage recorded',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }
}
