<?php

namespace Address\AddressModule\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use SoftDeletes;

    protected $table = 'addresses';

    protected $fillable = [
        'label',
        'address',
        'au_gnaf',
        'address_1',
        'address_2',
        'suburbcity',
        'stateprov',
        'postcode',
        'country',
        'lat',
        'lng',
    ];

    protected $dates = ['deleted_at', 'created_at', 'updated_at'];
}


