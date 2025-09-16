<?php

namespace Colors\ColorsModule\Models;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    protected $table = 'colors';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'uuid',
        'dark_text',
        'dark_bg',
        'dark_border',
        'light_text',
        'light_bg',
        'light_border',
    ];

    protected $casts = [
        'uuid' => 'string',
    ];

}
