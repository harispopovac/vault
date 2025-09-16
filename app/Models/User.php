<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Enums\Fit;

class User extends Authenticatable implements HasMedia
{
    use HasFactory, HasApiTokens, Notifiable, SoftDeletes, InteractsWithMedia;

    protected $guarded = ['id'];

    protected $hidden = [
        'password'
    ];

    protected $fillable = [
        'fname',
        'sname',
        'dob',
        'gender',
        'phone',
        'email',
        'password',
        'timezone',
        'google_id',
        'email_verified',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photo')->singleFile();
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('user-list-photo')
            ->fit(Fit::Crop, 34, 34)
            ->sharpen(5)
            ->quality(86)
            ->performOnCollections('photo')
            ->nonQueued();

        $this->addMediaConversion('user-profile-photo')
            ->fit(Fit::Crop, 200, 200)
            ->sharpen(5)
            ->quality(86)
            ->performOnCollections('photo')
            ->nonQueued();
    }

    public function getFullNameAttribute(): string
    {
        return $this->fname . ' ' . $this->sname;
    }

    public function workspaceMembers(): belongsToMany
    {
        return $this->belongsToMany(WorkspaceMember::class);
    }

    /**
     * Get the organisations that this user belongs to.
     */
    public function organisations(): BelongsToMany
    {
        return $this->belongsToMany(Organisation::class, 'organisation_users')
                    ->withTimestamps();
    }

    /**
     * Get the repositories that this user has access to.
     */
    public function repositories(): BelongsToMany
    {
        return $this->belongsToMany(Repository::class, 'repository_users')
                    ->withPivot(['role_id', 'is_admin'])
                    ->withTimestamps();
    }

    /**
     * Get the knowledge entries created by this user.
     */
    public function knowledgeEntries(): HasMany
    {
        return $this->hasMany(KnowledgeEntry::class);
    }
}
