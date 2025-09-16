<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnrollPhoneEmailRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'email|required|unique:users,email',
            'phone' => '' // TODO: Manage when we add phone validation
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required',
            'email.email' => 'Email is invalid',
            'email.unique' => 'Email is already taken',
        ];
    }
}
