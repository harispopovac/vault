<?php

namespace StaffRoster\StaffRosterModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CopyRosterRequest extends FormRequest
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
            'current_week' => 'required|array',
            'current_week.start' => 'required|date',
            'current_week.end' => 'required|date',
            'copy_to' => 'required|array',
            'copy_to.*.start' => 'required|date',
            'copy_to.*.end' => 'required|date',
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
            'current_week.required' => 'Current week is required',
            'current_week.array' => 'Current week must be an array',
            'current_week.start.required' => 'Current week start date is required',
            'current_week.start.date' => 'Current week start date must be a valid date',
            'current_week.end.required' => 'Current week end date is required',
            'current_week.end.date' => 'Current week end date must be a valid date',
            'copy_to.required' => 'Copy to weeks are required',
            'copy_to.array' => 'Copy to weeks must be an array',
            'copy_to.*.start.required' => 'Copy to week start date is required',
            'copy_to.*.start.date' => 'Copy to week start date must be a valid date',
            'copy_to.*.end.required' => 'Copy to week end date is required',
            'copy_to.*.end.date' => 'Copy to week end date must be a valid date',
        ];
    }
}
