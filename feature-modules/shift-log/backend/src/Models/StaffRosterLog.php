<?php

namespace ShiftLog\ShiftLogModule\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class StaffRosterLog extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $table = 'staff_roster_log';

    protected $guarded = ['id'];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'claimed_start',
        'claimed_end',
        'actual_start',
        'actual_end',
    ];

    protected $fillable = [
        'staff_id',
        'site_id',
        'staff_roster_id',
        'claimed_start',
        'claimed_end',
        'actual_start',
        'actual_end',
        'checkin_comments',
        'checkout_comments',
        'ot_claim',
        'ot_reason',
        'ot_response',
        'ot_responded_by',
        'ot_response_notes',
        'checkin_ip',
        'checkout_ip',
        'site_ip',
    ];

    protected $casts = [
        'claimed_start' => 'datetime',
        'claimed_end' => 'datetime',
        'actual_start' => 'datetime',
        'actual_end' => 'datetime',
        'ot_claim' => 'integer',
    ];

    /**
     * Staff relationship - configurable model
     */
    public function staff()
    {
        $staffModel = config('shift-log.staff_model', 'App\\Models\\User');
        return $this->belongsTo($staffModel, config('shift-log.staff_field', 'staff_id'));
    }

    /**
     * Site relationship - conditional based on configuration
     */
    public function site()
    {
        if (!config('shift-log.enable_sites', true)) {
            return null;
        }

        $siteModel = config('shift-log.site_model');
        $siteField = config('shift-log.site_field', 'site_id');

        return $this->belongsTo($siteModel, $siteField);
    }

    /**
     * Staff roster relationship - links to planned shift
     */
    public function staffRoster()
    {
        return $this->belongsTo('StaffRoster\\StaffRosterModule\\Models\\StaffRoster', 'staff_roster_id');
    }

    /**
     * Overtime responder relationship
     */
    public function overtimeResponder()
    {
        if (!config('shift-log.enable_overtime', false)) {
            return null;
        }

        $staffModel = config('shift-log.staff_model', 'App\\Models\\User');
        return $this->belongsTo($staffModel, 'ot_responded_by');
    }

    /**
     * Break records relationship - conditional based on configuration
     */
    public function breaks()
    {
        if (!config('shift-log.enable_breaks', false)) {
            return $this->hasMany(self::class, 'id', 'id')->whereRaw('1=0'); // Empty relation
        }

        return $this->hasMany(StaffRosterShiftBreak::class, 'shift_id');
    }

    /**
     * Media collections for photos when enabled
     */
    public function registerMediaCollections(): void
    {
        if (!config('shift-log.enable_photos', false)) {
            return;
        }

        $collections = config('shift-log.photo_collections', []);

        foreach ($collections as $collectionName) {
            $this->addMediaCollection($collectionName);
        }
    }

    /**
     * Scopes
     */
    public function scopeForSite($query, $siteId)
    {
        if (!config('shift-log.enable_sites', true)) {
            return $query;
        }

        if ($siteId === null) {
            return $query->whereNull(config('shift-log.site_field', 'site_id'));
        }

        return $query->where(config('shift-log.site_field', 'site_id'), $siteId);
    }

    public function scopeForStaff($query, $staffId)
    {
        return $query->where(config('shift-log.staff_field', 'staff_id'), $staffId);
    }

    public function scopeForDateRange($query, $startDate, $endDate = null)
    {
        $query->where('claimed_start', '>=', $startDate);

        if ($endDate) {
            $query->where('claimed_start', '<=', $endDate);
        }

        return $query;
    }

    public function scopeOpenShifts($query)
    {
        return $query->whereNotNull('actual_start')
                     ->whereNull('actual_end');
    }

    public function scopeCompletedShifts($query)
    {
        return $query->whereNotNull('actual_start')
                     ->whereNotNull('actual_end');
    }

    /**
     * Accessors
     */
    public function getIsOpenAttribute(): bool
    {
        return $this->actual_start !== null && $this->actual_end === null;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->actual_start !== null && $this->actual_end !== null;
    }

    public function getDurationMinutesAttribute(): ?int
    {
        if (!$this->claimed_start || !$this->claimed_end) {
            return null;
        }

        return $this->claimed_start->diffInMinutes($this->claimed_end);
    }

    public function getActualDurationMinutesAttribute(): ?int
    {
        if (!$this->actual_start || !$this->actual_end) {
            return null;
        }

        return $this->actual_start->diffInMinutes($this->actual_end);
    }

    /**
     * Calculate total break duration when breaks are enabled
     */
    public function calculateTotalBreakDuration(): int
    {
        if (!config('shift-log.enable_breaks', false)) {
            return 0;
        }

        return $this->breaks->sum(function ($break) {
            if (!$break->break_start || !$break->break_end) {
                return 0;
            }
            return $break->break_start->diffInSeconds($break->break_end);
        });
    }
}
