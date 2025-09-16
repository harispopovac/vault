<?php

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

class UploadPhotoRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'photo' => 'required'
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'The photo field is required.'
        ];
    }
}
