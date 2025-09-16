<?php

namespace ShiftLog\ShiftLogModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckOutRequest extends FormRequest
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
            'claimed_end' => 'required|date|after:' . $this->route('staffRosterLog')->claimed_start,
            'actual_end' => 'nullable|date', // Will be set to now() in controller
        ];

        // Add comment validation if comments are enabled
        if (config('shift-log.enable_comments', true)) {
            $rules['checkout_comments'] = 'nullable|string|max:1000';
        }

        // Add overtime fields if overtime is enabled
        if (config('shift-log.enable_overtime', false)) {
            $rules['ot_claim'] = 'nullable|integer|min:0';
            $rules['ot_reason'] = 'nullable|string|max:200';
            $rules['ot_response'] = 'nullable|in:A,R'; // A = Approved, R = Rejected
            $rules['ot_responded_by'] = 'nullable|integer|exists:' . config('shift-log.staff_table', 'users') . ',id';
            $rules['ot_response_notes'] = 'nullable|string|max:200';
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        $messages = [
            'claimed_end.required' => 'End time is required.',
            'claimed_end.date' => 'End time must be a valid date.',
            'claimed_end.after' => 'End time must be after start time.',
            'actual_end.date' => 'Actual end time must be a valid date.',
        ];

        if (config('shift-log.enable_comments', true)) {
            $messages['checkout_comments.max'] = 'Check-out comments must not exceed 1000 characters.';
        }

        if (config('shift-log.enable_overtime', false)) {
            $messages['ot_claim.integer'] = 'Overtime claim must be a valid number.';
            $messages['ot_claim.min'] = 'Overtime claim must be zero or greater.';
            $messages['ot_reason.max'] = 'Overtime reason must not exceed 200 characters.';
            $messages['ot_response.in'] = 'Overtime response must be either approved (A) or rejected (R).';
            $messages['ot_responded_by.exists'] = 'Selected overtime responder does not exist.';
            $messages['ot_response_notes.max'] = 'Overtime response notes must not exceed 200 characters.';
        }

        return $messages;
    }

    /**
     * Get validated data with defaults
     */
    public function getValidatedData(): array
    {
        $data = $this->validated();

        // Set actual end time to now if not provided
        $data['actual_end'] = $data['actual_end'] ?? now();

        // Add default values for optional fields
        if (config('shift-log.enable_comments', true)) {
            $data['checkout_comments'] = $data['checkout_comments'] ?? null;
        }

        if (config('shift-log.enable_overtime', false)) {
            $data['ot_claim'] = $data['ot_claim'] ?? null;
            $data['ot_reason'] = $data['ot_reason'] ?? null;
            $data['ot_response'] = $data['ot_response'] ?? null;
            $data['ot_responded_by'] = $data['ot_responded_by'] ?? null;
            $data['ot_response_notes'] = $data['ot_response_notes'] ?? null;
        }

        return $data;
    }
}
