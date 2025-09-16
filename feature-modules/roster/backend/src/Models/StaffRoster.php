<?php

namespace StaffRoster\StaffRosterModule\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffRoster extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff_roster';

    protected $guarded = ['id'];

    protected $fillable = [
        'staff_id',
        'site_id',
        'rostered_start',
        'rostered_end',
        'hourlyrate',
        'shiftrate',
        'absent_notice',
        'absence_noted_by',
        'absent_notes'
    ];

    protected $dates = [
        'rostered_start',
        'rostered_end',
        'absent_notice',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'rostered_start' => 'datetime',
        'rostered_end' => 'datetime',
        'absent_notice' => 'datetime',
        'hourlyrate' => 'decimal:2',
        'shiftrate' => 'decimal:2',
    ];

    // Relationships
    public function staff()
    {
        return $this->belongsTo(\App\Models\Staff::class, 'staff_id');
    }

    public function site()
    {
        if (!config('roster.enable_sites')) {
            return null;
        }

        $model = config('roster.site_model');
        $field = config('roster.site_field');

        return $this->belongsTo($model, $field);
    }

    public function absenceNotedBy()
    {
        return $this->belongsTo(\App\Models\Staff::class, 'absence_noted_by');
    }

    // Accessors
    public function getIsAbsentAttribute()
    {
        return !is_null($this->absent_notice) || !is_null($this->absent_notes);
    }

    // Scopes
    public function scopeForSite($query, $siteId)
    {
        if (!config('roster.enable_sites') || !$siteId) {
            return $query;
        }

        return $query->where(config('roster.site_field'), $siteId);
    }

    public function scopeForStaff($query, $staffId)
    {
        return $query->where('staff_id', $staffId);
    }

    public function scopeForPeriod($query, $start, $end)
    {
        return $query->where('rostered_start', '>=', $start)
                    ->where('rostered_end', '<=', $end);
    }

    public function scopeOverlapping($query, $start, $end)
    {
        return $query->where(function ($q) use ($start, $end) {
            $q->whereBetween('rostered_start', [$start, $end])
              ->orWhereBetween('rostered_end', [$start, $end])
              ->orWhere(function ($q1) use ($start, $end) {
                  $q1->where('rostered_start', '<=', $start)
                     ->where('rostered_end', '>=', $end);
              });
        });
    }
}
