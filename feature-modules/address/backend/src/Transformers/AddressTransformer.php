<?php

namespace Address\AddressModule\Transformers;

use Address\AddressModule\Models\Address;
use League\Fractal\TransformerAbstract;

class AddressTransformer extends TransformerAbstract
{
    public function transform(Address $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'address' => $address->address,
            'address_1' => $address->address_1,
            'address_2' => $address->address_2,
            'suburbcity' => $address->suburbcity,
            'postcode' => $address->postcode,
            'stateprov' => $address->stateprov,
            'country' => $address->country,
            'au_gnaf' => $address->au_gnaf,
            'lat' => $address->lat,
            'lng' => $address->lng,
        ];
    }
}



