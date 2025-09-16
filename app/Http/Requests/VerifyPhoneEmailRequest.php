<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPhoneEmailRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'otp' => 'required|string|min:6|max:6',
        ];
    }

    public function messages(): array
    {
        return [
            'otp.required' => 'OTP is required',
            'otp.string' => 'OTP must be a string',
            'otp.min' => 'OTP must be at least 6 characters',
            'otp.max' => 'OTP must not be greater than 6 characters',
        ];
    }
}
