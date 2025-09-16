<?php

namespace Sites\SitesModule\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sites';

    protected $fillable = [
        'name',
        'label',
        'phone',
        'email',
        'fax',
        'address_id',
        'address_1',
        'address_2',
        'suburbcity',
        'postcode',
        'stateprov',
        'country',
        'timezone',
        'currency',
        'status',
        'organisation_id',
        'maintenance_team',
        'truck_booking_target',
        'lolf_booking_target',
        'force_timeslot_use',
    ];

    protected $casts = [
        'truck_booking_target' => 'integer',
        'lolf_booking_target' => 'integer',
        'force_timeslot_use' => 'boolean',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    // Relationships
    public function address()
    {
        return $this->belongsTo(\App\Models\Address::class, 'address_id');
    }

    public function organisation()
    {
        return $this->belongsTo(\App\Models\Organisation::class, 'organisation_id');
    }

    public function timezone()
    {
        return $this->belongsTo(\App\Models\Timezone::class, 'timezone');
    }

    public function managers()
    {
        return $this->belongsToMany(\App\Models\Staff::class, 'site_managers', 'site_id', 'staff_id');
    }

    public function buildings()
    {
        return $this->hasMany(\App\Models\Building::class);
    }

    public function rooms()
    {
        return $this->hasMany(\App\Models\Room::class);
    }

    public function staff()
    {
        return $this->belongsToMany(\App\Models\Staff::class, 'staff_roles', 'site_id', 'staff_id');
    }

    // Accessors
    public function getDisplayNameAttribute()
    {
        return $this->label ?: $this->name;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeForOrganisation($query, $organisationId)
    {
        return $query->where('organisation_id', $organisationId);
    }
}
