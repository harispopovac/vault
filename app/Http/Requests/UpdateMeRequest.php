<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'fname' => 'string|required',
            'sname' => 'string|required',
            'email' => 'string|nullable|email',
            'dob' => 'date|nullable',
            'gender' => 'nullable|string|in:M,F,U',
            'address' => 'array|nullable',
            'phone' => 'string|nullable',
            'timezone' => 'string|required',
            'currency' => 'string|required',
            'blur_financials' => 'Boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'fname.required' => 'Name is required',
            'fname.string' => 'Name must be a string',
            'sname.required' => 'Surname is required',
            'sname.string' => 'Surname must be a string',
            'dob.date' => 'Date of Birth must be a valid date',
            'gender.string' => 'Gender must be a string',
            'gender.in' => 'Gender must be one of M, F, U',
            'phone.string' => 'Phone must be a string',
            'email.required' => 'Email is required',
            'email.string' => 'Email must be a string',
            'email.unique' => 'The email has already been taken',
            'email.email' => 'Email must be a valid email address',
            'timezone.required' => 'Timezone is required',
            'timezone.string' => 'Timezone must be a string',
            'currency.string' => 'Currency must be a string',
            'currency.required' => 'Currency is required',
            'blur_financials.boolean' => 'Blur Financials must be boolean',
        ];
    }
}
