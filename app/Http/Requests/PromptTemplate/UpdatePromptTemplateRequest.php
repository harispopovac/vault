<?php

namespace App\Http\Requests\PromptTemplate;

use App\Models\PromptTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePromptTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $templateId = $this->route('prompt_template')->id;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:255',
                function ($attribute, $value, $fail) use ($templateId) {
                    // Check uniqueness within organisation (excluding current template)
                    $user = auth()->user();
                    $organisation = $user->organisations()->first();
                    
                    if ($organisation) {
                        $existing = PromptTemplate::forOrganisation($organisation->id)
                            ->where('name', $value)
                            ->where('id', '!=', $templateId)
                            ->exists();
                        
                        if ($existing) {
                            $fail('A template with this name already exists in your organisation.');
                        }
                    }
                },
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
            'field_definitions' => [
                'sometimes',
                'required',
                'array',
                'min:1',
            ],
            'field_definitions.*' => [
                'sometimes',
                'required',
                'array',
            ],
            'field_definitions.*.type' => [
                'sometimes',
                'required',
                'in:text,checklist,ranking',
            ],
            'field_definitions.*.label' => [
                'sometimes',
                'required',
                'string',
                'min:1',
                'max:255',
            ],
            'field_definitions.*.required' => [
                'sometimes',
                'nullable',
                'boolean',
            ],
            'field_definitions.*.placeholder' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'field_definitions.*.options' => [
                'sometimes',
                'required_if:field_definitions.*.type,checklist,ranking',
                'array',
                'min:2',
            ],
            'field_definitions.*.options.*' => [
                'sometimes',
                'required',
                'string',
                'min:1',
                'max:255',
            ],
            'is_active' => [
                'sometimes',
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Template name is required',
            'name.min' => 'Template name must be at least 3 characters',
            'name.max' => 'Template name must not exceed 255 characters',
            'description.max' => 'Description must not exceed 1000 characters',
            'field_definitions.required' => 'At least one field definition is required',
            'field_definitions.*.type.required' => 'Field type is required',
            'field_definitions.*.type.in' => 'Field type must be one of: text, checklist, ranking',
            'field_definitions.*.label.required' => 'Field label is required',
            'field_definitions.*.label.min' => 'Field label cannot be empty',
            'field_definitions.*.options.required_if' => 'Options are required for checklist and ranking fields',
            'field_definitions.*.options.min' => 'At least 2 options are required for checklist and ranking fields',
        ];
    }
}
