<?php

namespace App\Http\Requests\Repository;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRepositoryRequest extends FormRequest
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
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'webhook_secret' => [
                'sometimes',
                'required',
                'string',
                'min:16',
                'max:255',
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Repository name is required',
            'name.string' => 'Repository name must be a string',
            'name.max' => 'Repository name must not exceed 255 characters',
            'webhook_secret.required' => 'Webhook secret is required',
            'webhook_secret.min' => 'Webhook secret must be at least 16 characters long',
            'webhook_secret.max' => 'Webhook secret must not exceed 255 characters',
        ];
    }
}