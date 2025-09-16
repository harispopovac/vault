<?php

namespace StaffRoster\StaffRosterModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClearRosterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            'start' => 'required_without:day|date',
            'end' => 'required_without:day|date',
            'day' => 'required_without:start|date',
        ];

        // Only add site validation if sites are enabled
        if (config('roster.enable_sites')) {
            $siteField = config('roster.site_field');
            $siteTable = config('roster.site_table');

            $rules[$siteField] = "nullable|integer|exists:{$siteTable},id";
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'site_id.integer' => 'Site ID must be an integer',
            'site_id.exists' => 'Selected site does not exist',
            'start.required_without' => 'Start date is required when day is not provided',
            'start.date' => 'Start date must be a valid date',
            'end.required_without' => 'End date is required when day is not provided',
            'end.date' => 'End date must be a valid date',
            'day.required_without' => 'Day is required when start date is not provided',
            'day.date' => 'Day must be a valid date',
        ];
    }
}
