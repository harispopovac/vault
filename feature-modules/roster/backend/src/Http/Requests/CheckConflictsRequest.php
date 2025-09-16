<?php

namespace StaffRoster\StaffRosterModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckConflictsRequest extends FormRequest
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
            'staff_id' => 'required|integer|exists:staff,id',
            'rostered_start' => 'required|date',
            'rostered_end' => 'required|date|after_or_equal:rostered_start',
            'id' => 'nullable|integer|exists:staff_roster,id',
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
            'staff_id.required' => 'Staff member is required',
            'staff_id.integer' => 'Staff member ID must be an integer',
            'staff_id.exists' => 'Selected staff member does not exist',
            'site_id.required' => 'Site is required',
            'site_id.integer' => 'Site ID must be an integer',
            'site_id.exists' => 'Selected site does not exist',
            'rostered_start.required' => 'Rostered start time is required',
            'rostered_start.date' => 'Rostered start time must be a valid date',
            'rostered_end.required' => 'Rostered end time is required',
            'rostered_end.date' => 'Rostered end time must be a valid date',
            'rostered_end.after_or_equal' => 'Rostered end time must be after or equal to start time',
            'exclude_id.integer' => 'Exclude ID must be an integer',
            'exclude_id.exists' => 'Selected roster to exclude does not exist',
        ];
    }
}
