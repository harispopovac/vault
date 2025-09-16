<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trigger extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'repository_id',
        'prompt_id',
        'github_events',
        'event_filters',
        'target_type',
        'target_users',
        'target_roles',
        'github_webhook_id',
        'webhook_secret',
        'webhook_active',
        'is_active',
        'delivery_delay_minutes',
        'organization_id',
        'created_by',
    ];

    protected $casts = [
        'github_events' => 'array',
        'event_filters' => 'array',
        'target_users' => 'array',
        'target_roles' => 'array',
        'github_webhook_id' => 'integer',
        'webhook_active' => 'boolean',
        'is_active' => 'boolean',
        'delivery_delay_minutes' => 'integer',
    ];

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function prompt(): BelongsTo
    {
        return $this->belongsTo(Prompt::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(TriggerDelivery::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForRepository($query, $repositoryId)
    {
        return $query->where('repository_id', $repositoryId);
    }

    public function scopeForEvent($query, $eventType)
    {
        return $query->whereJsonContains('github_events', $eventType);
    }
}
