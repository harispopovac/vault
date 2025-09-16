<?php

namespace ShiftLog\ShiftLogModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
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
            config('shift-log.staff_field', 'staff_id') => 'required|integer|exists:' . config('shift-log.staff_table', 'users') . ',id',
            'staff_roster_id' => 'nullable|integer|exists:staff_roster,id',
            'claimed_start' => 'required|date',
            'claimed_end' => 'nullable|date|after:claimed_start',
        ];

        // Add site validation if sites are enabled
        if (config('shift-log.enable_sites', true)) {
            $siteTable = config('shift-log.site_table', 'sites');
            $siteField = config('shift-log.site_field', 'site_id');
            $rules[$siteField] = 'nullable|integer|exists:' . $siteTable . ',id';
        }

        // Add comment validation if comments are enabled
        if (config('shift-log.enable_comments', true)) {
            $rules['checkin_comments'] = 'nullable|string|max:1000';
        }

        // Add overtime fields if overtime is enabled
        if (config('shift-log.enable_overtime', false)) {
            $rules['ot_claim'] = 'nullable|integer|min:0';
            $rules['ot_reason'] = 'nullable|string|max:200';
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        $staffField = config('shift-log.staff_field', 'staff_id');
        $siteField = config('shift-log.site_field', 'site_id');

        $messages = [
            $staffField . '.required' => 'Staff member is required.',
            $staffField . '.exists' => 'Selected staff member does not exist.',
            'staff_roster_id.exists' => 'Selected roster entry does not exist.',
            'claimed_start.required' => 'Start time is required.',
            'claimed_start.date' => 'Start time must be a valid date.',
            'claimed_end.date' => 'End time must be a valid date.',
            'claimed_end.after' => 'End time must be after start time.',
        ];

        if (config('shift-log.enable_sites', true)) {
            $messages[$siteField . '.exists'] = 'Selected site does not exist.';
        }

        if (config('shift-log.enable_comments', true)) {
            $messages['checkin_comments.max'] = 'Check-in comments must not exceed 1000 characters.';
        }

        if (config('shift-log.enable_overtime', false)) {
            $messages['ot_claim.integer'] = 'Overtime claim must be a valid number.';
            $messages['ot_claim.min'] = 'Overtime claim must be zero or greater.';
            $messages['ot_reason.max'] = 'Overtime reason must not exceed 200 characters.';
        }

        return $messages;
    }

    /**
     * Get validated data with defaults
     */
    public function getValidatedData(): array
    {
        $data = $this->validated();

        // Add default values for optional fields
        if (config('shift-log.enable_sites', true)) {
            $siteField = config('shift-log.site_field', 'site_id');
            $data[$siteField] = $data[$siteField] ?? null;
        }

        if (config('shift-log.enable_comments', true)) {
            $data['checkin_comments'] = $data['checkin_comments'] ?? null;
        }

        if (config('shift-log.enable_overtime', false)) {
            $data['ot_claim'] = $data['ot_claim'] ?? null;
            $data['ot_reason'] = $data['ot_reason'] ?? null;
        }

        return $data;
    }
}
