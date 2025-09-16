<?php

namespace Address\AddressModule\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'address_1' => 'required|string|max:255',
            'address_2' => 'nullable|string|max:255',
            'suburbcity' => 'required|string|max:255',
            'stateprov' => 'required|string|max:50',
            'postcode' => 'required|string|max:20',
            'country' => 'required|string|size:2',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ];
    }
}


