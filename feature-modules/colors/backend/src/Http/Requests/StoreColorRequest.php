<?php

namespace Colors\ColorsModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreColorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:15',
            'dark_text' => 'required|string|max:10',
            'dark_bg' => 'required|string|max:10',
            'dark_border' => 'required|string|max:10',
            'light_text' => 'required|string|max:10',
            'light_bg' => 'required|string|max:10',
            'light_border' => 'required|string|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'name.string' => 'The name field must be a string.',
            'name.max' => 'The name field must be less than 15 characters.',
            'dark_text.required' => 'The dark text field is required.',
            'dark_text.string' => 'The dark text field must be a string.',
            'dark_text.max' => 'The dark text field must be less than 10 characters.',
            'dark_bg.required' => 'The dark background field is required.',
            'dark_bg.string' => 'The dark background field must be a string.',
            'dark_bg.max' => 'The dark background field must be less than 10 characters.',
            'dark_border.required' => 'The dark border field is required.',
            'dark_border.string' => 'The dark border field must be a string.',
            'dark_border.max' => 'The dark border field must be less than 10 characters.',
            'light_text.required' => 'The light text field is required.',
            'light_text.string' => 'The light text field must be a string.',
            'light_text.max' => 'The light text field must be less than 10 characters.',
            'light_bg.required' => 'The light background field is required.',
            'light_bg.string' => 'The light background field must be a string.',
            'light_bg.max' => 'The light background field must be less than 10 characters.',
            'light_border.required' => 'The light border field is required.',
            'light_border.string' => 'The light border field must be a string.',
            'light_border.max' => 'The light border field must be less than 10 characters.',
        ];
    }
}
