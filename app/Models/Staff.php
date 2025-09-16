<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Enums\Fit;

class Staff extends Authenticatable implements HasMedia
{
    use HasFactory, HasApiTokens, Notifiable, SoftDeletes, InteractsWithMedia;

    protected $table = 'staff';

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $fillable = [
        'fname',
        'sname',
        'dob',
        'gender',
        'phone',
        'email',
        'password',
        'status',
        'address_id',
        'timezone',
        'account_type',
        'mfa_totp_secret',
        'mfa_sms_phone',
        'mfa_email',
        'mfa_default',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'dob' => 'date',
    ];

    // Media collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('staff-profile')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 150, 150)
            ->sharpen(10)
            ->quality(90)
            ->performOnCollections('staff-profile');

        $this->addMediaConversion('profile')
            ->fit(Fit::Crop, 300, 300)
            ->sharpen(10)
            ->quality(85)
            ->performOnCollections('staff-profile');
    }

    // Relationships
    public function staffRosters(): HasMany
    {
        return $this->hasMany(\App\Models\StaffRoster::class, 'staff_id');
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Address::class);
    }

    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Site::class, 'staff_sites', 'staff_id', 'site_id');
    }

    public function roles(): HasMany
    {
        return $this->hasMany(\App\Models\StaffRole::class, 'staff_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Team::class, 'staff_teams', 'staff_id', 'team_id');
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim($this->fname . ' ' . $this->sname);
    }

    public function getPhotoAttribute(): ?string
    {
        return $this->getFirstMediaUrl('staff-profile', 'profile');
    }

    public function getPhotoThumbAttribute(): ?string
    {
        return $this->getFirstMediaUrl('staff-profile', 'thumb');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('fname', 'like', "%{$search}%")
              ->orWhere('sname', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        // Automatically hash passwords
        static::creating(function ($staff) {
            if ($staff->password && !str_starts_with($staff->password, '$2y$')) {
                $staff->password = bcrypt($staff->password);
            }
        });

        static::updating(function ($staff) {
            if ($staff->isDirty('password') && $staff->password && !str_starts_with($staff->password, '$2y$')) {
                $staff->password = bcrypt($staff->password);
            }
        });
    }
}
