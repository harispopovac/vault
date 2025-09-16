<?php

namespace App\Repositories;

use App\Models\PromptTemplate;
use App\Models\User;
use App\Transformers\PromptTemplate\PromptTemplateTransformer;
use Exception;

class PromptTemplateRepository
{
    protected PromptTemplateTransformer $transformer;

    public function __construct(PromptTemplateTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    /**
     * Get all prompt templates for the user's organisation.
     */
    public function index(int $userId): array
    {
        $user = User::findOrFail($userId);
        
        // Get the user's primary organisation (assuming first one for now)
        $organisation = $user->organisations()->first();
        
        if (!$organisation) {
            throw new Exception('User does not belong to any organisation');
        }

        $templates = PromptTemplate::forOrganisation($organisation->id)
            ->with(['creator', 'organisation'])
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'data' => $this->transformer->transformCollection($templates->toArray())
        ];
    }

    /**
     * Store a new prompt template.
     */
    public function store(array $data, int $userId): array
    {
        $user = User::findOrFail($userId);
        
        // Get the user's primary organisation
        $organisation = $user->organisations()->first();
        
        if (!$organisation) {
            throw new Exception('User does not belong to any organisation');
        }

        // Validate field definitions structure
        $this->validateFieldDefinitions($data['field_definitions']);

        $template = PromptTemplate::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'field_definitions' => $data['field_definitions'],
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $userId,
            'organisation_id' => $organisation->id,
        ]);

        // Refresh to get default values from database
        $template->refresh();
        $template->load(['creator', 'organisation']);

        return [
            'data' => $this->transformer->transform($template->toArray())
        ];
    }

    /**
     * Get a specific prompt template.
     */
    public function show(PromptTemplate $template, int $userId): array
    {
        $user = User::findOrFail($userId);
        
        // Check if user belongs to the same organisation as the template
        if (!$user->organisations()->where('organisations.id', $template->organisation_id)->exists()) {
            throw new Exception('Access denied to this template');
        }

        $template->load(['creator', 'organisation']);

        return [
            'data' => $this->transformer->transform($template->toArray())
        ];
    }

    /**
     * Update a prompt template.
     */
    public function update(PromptTemplate $template, array $data, int $userId): array
    {
        $user = User::findOrFail($userId);
        
        // Check if user belongs to the same organisation as the template
        if (!$user->organisations()->where('organisations.id', $template->organisation_id)->exists()) {
            throw new Exception('Access denied to this template');
        }

        // Validate field definitions structure if provided
        if (isset($data['field_definitions'])) {
            $this->validateFieldDefinitions($data['field_definitions']);
        }

        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'description' => $data['description'] ?? null,
            'field_definitions' => $data['field_definitions'] ?? null,
            'is_active' => $data['is_active'] ?? null,
        ], function ($value) {
            return $value !== null;
        });

        $template->update($updateData);
        $template->load(['creator', 'organisation']);

        return [
            'data' => $this->transformer->transform($template->toArray())
        ];
    }

    /**
     * Delete a prompt template (soft delete).
     */
    public function destroy(PromptTemplate $template, int $userId): bool
    {
        $user = User::findOrFail($userId);
        
        // Check if user belongs to the same organisation as the template
        if (!$user->organisations()->where('organisations.id', $template->organisation_id)->exists()) {
            throw new Exception('Access denied to this template');
        }

        return $template->delete();
    }

    /**
     * Increment usage count for a template.
     */
    public function incrementUsage(PromptTemplate $template, int $userId): bool
    {
        $user = User::findOrFail($userId);
        
        // Check if user belongs to the same organisation as the template
        if (!$user->organisations()->where('organisations.id', $template->organisation_id)->exists()) {
            throw new Exception('Access denied to this template');
        }

        return $template->incrementUsage();
    }

    /**
     * Validate the field definitions structure.
     */
    protected function validateFieldDefinitions(array $fieldDefinitions): void
    {
        $allowedTypes = ['text', 'checklist', 'ranking'];

        foreach ($fieldDefinitions as $field) {
            if (!isset($field['type']) || !in_array($field['type'], $allowedTypes)) {
                throw new Exception('Invalid field type. Allowed types: ' . implode(', ', $allowedTypes));
            }

            if (!isset($field['label']) || empty($field['label'])) {
                throw new Exception('Field label is required');
            }

            // Validate specific field type requirements
            switch ($field['type']) {
                case 'checklist':
                    if (!isset($field['options']) || !is_array($field['options']) || count($field['options']) < 2) {
                        throw new Exception('Checklist fields must have at least 2 options');
                    }
                    break;
                    
                case 'ranking':
                    if (!isset($field['options']) || !is_array($field['options']) || count($field['options']) < 2) {
                        throw new Exception('Ranking fields must have at least 2 options');
                    }
                    break;
            }
        }
    }
}