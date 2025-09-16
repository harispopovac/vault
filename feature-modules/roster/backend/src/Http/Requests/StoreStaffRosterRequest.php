<?php

namespace StaffRoster\StaffRosterModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRosterRequest extends FormRequest
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
            'staff_ids' => 'required|array',
            'staff_ids.*' => 'required|integer|exists:staff,id',
            'rostered_start' => 'required|date',
            'rostered_end' => 'required|date|after_or_equal:rostered_start',
            'hourlyrate' => 'nullable|numeric|min:0',
            'shiftrate' => 'nullable|numeric|min:0',
            'absent_notice' => 'nullable|date',
            'absence_noted_by' => 'nullable|integer|exists:staff,id',
            'absent_notes' => 'nullable|string'
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
            'staff_ids.required' => 'Staff members are required',
            'staff_ids.array' => 'Staff members must be an array',
            'staff_ids.*.required' => 'Staff member ID is required',
            'staff_ids.*.integer' => 'Staff member ID must be an integer',
            'staff_ids.*.exists' => 'Selected staff member does not exist',
            'site_id.integer' => 'Site ID must be an integer',
            'site_id.exists' => 'Selected site does not exist',
            'rostered_start.required' => 'Rostered start time is required',
            'rostered_start.date' => 'Rostered start time must be a valid date',
            'rostered_end.required' => 'Rostered end time is required',
            'rostered_end.date' => 'Rostered end time must be a valid date',
            'rostered_end.after_or_equal' => 'Rostered end time must be after or equal to start time',
            'hourlyrate.numeric' => 'Hourly rate must be a number',
            'hourlyrate.min' => 'Hourly rate cannot be negative',
            'shiftrate.numeric' => 'Shift rate must be a number',
            'shiftrate.min' => 'Shift rate cannot be negative',
            'absent_notice.date' => 'Absent notice must be a valid date',
            'absence_noted_by.integer' => 'Absence noted by must be an integer',
            'absence_noted_by.exists' => 'Selected staff member for absence noted by does not exist',
            'absent_notes.string' => 'Absent notes must be a string'
        ];
    }
}
