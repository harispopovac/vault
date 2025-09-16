<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RequestPasswordResetRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:staff,email',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Please enter your email',
            'email.exists' => 'There is no account with the entered email address.',
        ];
    }
}
