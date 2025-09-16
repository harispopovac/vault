<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TriggerDelivery extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'trigger_id',
        'target_user_id',
        'github_event_type',
        'github_payload',
        'github_delivery_id',
        'status',
        'delivered_at',
        'responded_at',
        'failure_reason',
        'response_data',
        'knowledge_entry_id',
        'retry_count',
        'next_retry_at',
    ];

    protected $casts = [
        'github_payload' => 'array',
        'response_data' => 'array',
        'delivered_at' => 'datetime',
        'responded_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'retry_count' => 'integer',
    ];

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(Trigger::class);
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function knowledgeEntry(): BelongsTo
    {
        return $this->belongsTo(KnowledgeEntry::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeResponded($query)
    {
        return $query->where('status', 'responded');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForRetry($query)
    {
        return $query->where('status', 'failed')
                    ->where('next_retry_at', '<=', now());
    }
}
