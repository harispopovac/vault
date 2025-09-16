<?php

namespace App\Transformers\PromptTemplate;

use App\Models\PromptTemplate;
use League\Fractal\TransformerAbstract;

class PromptTemplateTransformer extends TransformerAbstract
{
    public function transform($template): array
    {
        // Handle both array and model instances
        $templateData = is_array($template) ? $template : $template->toArray();

        return [
            'id' => $templateData['id'],
            'name' => $templateData['name'],
            'description' => $templateData['description'],
            'field_definitions' => $templateData['field_definitions'],
            'is_active' => $templateData['is_active'],
            'usage_count' => $templateData['usage_count'] ?? 0,
            'creator' => isset($templateData['creator']) ? [
                'id' => $templateData['creator']['id'],
                'name' => $templateData['creator']['fname'] . ' ' . $templateData['creator']['sname'],
                'email' => $templateData['creator']['email'],
            ] : null,
            'organisation' => isset($templateData['organisation']) ? [
                'id' => $templateData['organisation']['id'],
                'name' => $templateData['organisation']['name'],
            ] : null,
            'created_at' => $templateData['created_at'],
            'updated_at' => $templateData['updated_at'],
        ];
    }

    /**
     * Transform a collection of templates.
     */
    public function transformCollection(array $templates): array
    {
        return array_map(function ($template) {
            return $this->transform($template);
        }, $templates);
    }
}