<?php

namespace Colors\ColorsModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateColorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:15',
            'dark_text' => 'sometimes|string|max:10',
            'dark_bg' => 'sometimes|string|max:10',
            'dark_border' => 'sometimes|string|max:10',
            'light_text' => 'sometimes|string|max:10',
            'light_bg' => 'sometimes|string|max:10',
            'light_border' => 'sometimes|string|max:10',
        ];
    }
}
