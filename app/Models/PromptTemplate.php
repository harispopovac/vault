<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromptTemplate extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'field_definitions',
        'is_active',
        'created_by',
        'organisation_id',
    ];

    protected $casts = [
        'field_definitions' => 'array',
        'is_active' => 'boolean',
        'usage_count' => 'integer',
    ];

    /**
     * Get the user who created this template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the organisation this template belongs to.
     */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    /**
     * Increment the usage count when template is used.
     */
    public function incrementUsage(): bool
    {
        return $this->increment('usage_count');
    }

    /**
     * Scope for active templates only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for templates belonging to a specific organisation.
     */
    public function scopeForOrganisation($query, int $organisationId)
    {
        return $query->where('organisation_id', $organisationId);
    }
}
