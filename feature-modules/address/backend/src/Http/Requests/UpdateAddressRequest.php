<?php

namespace Address\AddressModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_1' => 'sometimes|required|string|max:255',
            'address_2' => 'nullable|string|max:255',
            'suburbcity' => 'sometimes|required|string|max:255',
            'stateprov' => 'sometimes|required|string|max:50',
            'postcode' => 'sometimes|required|string|max:20',
            'country' => 'sometimes|required|string|size:2',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ];
    }
}


