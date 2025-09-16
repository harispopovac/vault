<?php

namespace App\Transformers\User;

use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    public function transform($data): array
    {
        return [
            'id' => $data['id'],

            'fname' => $data['fname'],
            'sname' => $data['sname'],
//            'fullName' => $data['fullName'],
            'dob' => $data['dob'],
            'gender' => $data['gender'],

            'phone' => $data['phone'],
            'email' => $data['email'],

            // Photo
//            'photo' => $data->getMedia('photo')->last()?->getFullUrl(),
        ];
    }
}
