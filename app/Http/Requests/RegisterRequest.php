<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|string|email',
            'fname' => 'required|string',
            'sname' => 'required|string',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|same:password',
            'timezone' => 'required|string',
            'marketing_consent' => 'nullable|boolean',
            'terms' => 'required|boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required',
            'email.string' => 'Email must be a string',
            'email.email' => 'Email must be a valid email address',
            'fname.required' => 'First name is required',
            'fname.string' => 'First name must be a string',
            'sname.required' => 'Surname is required',
            'sname.string' => 'Surname must be a string',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password_confirmation.required' => 'Password confirmation is required',
            'password_confirmation.same' => 'Passwords do not match',
            'timezone.required' => 'Timezone is required',
            'timezone.string' => 'Timezone must be a string',
            'marketing_consent.boolean' => 'Marketing consent must be a boolean',
            'terms.required' => 'Terms must be accepted',
            'terms.boolean' => 'Terms must be accepted'
        ];
    }
}
