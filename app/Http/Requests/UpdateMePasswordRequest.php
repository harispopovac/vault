<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMePasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'old_password' => 'nullable|string',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'old_password.string' => 'Old password must be a string',
            'password.required' => 'New password is required',
            'password.string' => 'New password must be a string',
            'password.min' => 'New password must be at least 6 characters',
            'password_confirmation.required' => 'Password confirmation is required',
            'password_confirmation.string' => 'Password confirmation must be a string',
        ];
    }
}
