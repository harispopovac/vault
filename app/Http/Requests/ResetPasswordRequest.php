<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|confirmed|min:8',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'An email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.exists' => 'No account found with that email address.',
            'token.required' => 'A reset token is required.',
            'password.required' => 'Please enter a password.',
            'password.confirmed' => 'Passwords do not match.',
            'password.min' => 'The password must be at least 8 characters.',
        ];
    }
}
