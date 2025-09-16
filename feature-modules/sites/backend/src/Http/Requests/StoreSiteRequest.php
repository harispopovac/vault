<?php

namespace Sites\SitesModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSiteRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:100',
            'label' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:200',
            'fax' => 'nullable|string|max:20',
            'timezone' => 'nullable|string|max:200',
            'currency' => 'nullable|string|max:3',
            'status' => 'nullable|string|in:open,tempclosed,permclosed',
            'organisation_id' => 'nullable|integer|exists:organisations,id',
            'maintenance_team' => 'nullable|integer',
            'truck_booking_target' => 'nullable|integer|min:0',
            'lolf_booking_target' => 'nullable|integer|min:0',
            'force_timeslot_use' => 'nullable|boolean',

            // Address fields
            'address_id' => 'nullable|integer|exists:addresses,id',
            'address_1' => 'nullable|string|max:200',
            'address_2' => 'nullable|string|max:200',
            'suburbcity' => 'nullable|string|max:100',
            'postcode' => 'nullable|string|max:10',
            'stateprov' => 'nullable|string|in:ACT,NSW,NT,QLD,SA,TAS,VIC,WA',
            'country' => 'nullable|string|max:2',

            // Nested address object
            'address' => 'nullable|array',
            'address.label' => 'nullable|string|max:100',
            'address.address' => 'nullable|string',
            'address.address_1' => 'nullable|string|max:200',
            'address.address_2' => 'nullable|string|max:200',
            'address.suburbcity' => 'nullable|string|max:100',
            'address.postcode' => 'nullable|string|max:10',
            'address.stateprov' => 'nullable|string|in:ACT,NSW,NT,QLD,SA,TAS,VIC,WA',
            'address.country' => 'nullable|string|max:2',
            'address.lat' => 'nullable|numeric',
            'address.lng' => 'nullable|numeric',

            // Manager assignments
            'manager_ids' => 'nullable|array',
            'manager_ids.*' => 'integer|exists:staff,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The site name is required.',
            'name.max' => 'The site name cannot exceed 100 characters.',
            'email.email' => 'Please enter a valid email address.',
            'stateprov.in' => 'Please select a valid Australian state or territory.',
            'address.stateprov.in' => 'Please select a valid Australian state or territory for the address.',
            'manager_ids.*.exists' => 'One or more selected managers do not exist.',
            'currency.max' => 'Currency code must be 3 characters.',
            'phone.max' => 'Phone number cannot exceed 20 characters.',
            'fax.max' => 'Fax number cannot exceed 20 characters.',
        ];
    }
}
