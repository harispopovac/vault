<?php

namespace StaffRoster\StaffRosterModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CopyDayRequest extends FormRequest
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
            'source_day_index' => 'required|integer',
            'copy_to' => 'required|array',
            'copy_to.*' => 'integer|min:0|max:6',
            'week_start' => 'required|date',
            'week_end' => 'required|date',
        ];

        // Only add site validation if sites are enabled
        if (config('roster.enable_sites')) {
            $siteField = config('roster.site_field');

            $rules[$siteField] = "nullable|integer";
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
            'source_day_index.required' => 'Source day index is required',
            'source_day_index.integer' => 'Source day index must be an integer',
            'copy_to.required' => 'Copy to days are required',
            'copy_to.array' => 'Copy to days must be an array',
            'copy_to.*.integer' => 'Copy to day must be an integer',
            'copy_to.*.min' => 'Copy to day must be at least 0',
            'copy_to.*.max' => 'Copy to day must be at most 6',
            'week_start.required' => 'Week start date is required',
            'week_start.date' => 'Week start date must be a valid date',
            'week_end.required' => 'Week end date is required',
            'week_end.date' => 'Week end date must be a valid date',
        ];
    }
}
