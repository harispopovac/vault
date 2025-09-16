<?php

namespace ShiftLog\ShiftLogModule\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffRosterShiftBreak extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'staff_roster_shift_breaks';

    protected $guarded = ['id'];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'break_start',
        'break_end',
    ];

    protected $fillable = [
        'shift_id',
        'approved_break',
        'break_start',
        'break_end',
        'notes',
    ];

    protected $casts = [
        'break_start' => 'datetime',
        'break_end' => 'datetime',
    ];

    /**
     * Shift log relationship
     */
    public function shiftLog()
    {
        return $this->belongsTo(StaffRosterLog::class, 'shift_id');
    }

    /**
     * Approved break relationship (if using approved break system)
     */
    public function approvedBreak()
    {
        $approvedBreaksTable = config('shift-log.approved_breaks_table', 'staff_approved_breaks');

        // For now, return null - can be implemented when approved breaks are needed
        return null;
    }

    /**
     * Scopes
     */
    public function scopeForShift($query, $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    public function scopeActiveBreaks($query)
    {
        return $query->whereNotNull('break_start');
    }

    public function scopeCompletedBreaks($query)
    {
        return $query->whereNotNull('break_start')
                     ->whereNotNull('break_end');
    }

    /**
     * Accessors
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->break_start !== null && $this->break_end === null;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->break_start !== null && $this->break_end !== null;
    }

    public function getDurationMinutesAttribute(): ?int
    {
        if (!$this->break_start || !$this->break_end) {
            return null;
        }

        return $this->break_start->diffInMinutes($this->break_end);
    }

    public function getDurationSecondsAttribute(): ?int
    {
        if (!$this->break_start || !$this->break_end) {
            return null;
        }

        return $this->break_start->diffInSeconds($this->break_end);
    }
}
