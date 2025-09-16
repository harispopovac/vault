<?php

namespace FilesManagement\FilesManagementModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadFilesRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'files' => 'required|array',
            'files.*' => 'required|file|max:10240', // 10MB max
            'model_type' => 'required|string|max:255',
            'model_id' => 'required|integer',
            'collection_name' => 'required|string|max:255',
            'single' => 'nullable|boolean'
        ];
    }
} 
