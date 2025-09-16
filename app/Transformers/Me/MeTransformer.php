<?php

namespace App\Transformers\Me;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use League\Fractal\TransformerAbstract;

class MeTransformer extends TransformerAbstract
{
    public function transform(User $user): array
    {
        return [
            'id' => $user['id'],

            'account_type' => $user['account_type'],

            'isGoogle' => (bool) $user['google_id'],
            'isPassword' => (bool) $user['password'],

            'fname' => $user['fname'],
            'sname' => $user['sname'],
            'fullName' => $user['fullName'],
            'dob' => $user['dob'],
            'gender' => $user['gender'],

            'phone' => $user['phone'],
            'email' => $user['email'],

            'timezone' => $user['timezone'],

            // Photo
            'photo' => $user->getMedia('photo')->last()?->getFullUrl(),
        ];
    }
}
