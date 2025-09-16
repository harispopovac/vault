<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeEntry extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'repository_id',
        'user_id',
        'pr_id',
        'pr_url',
        'pr_title',
        'pr_merge_commit_sha',
        'knowledge_entry',
        'category',
    ];

    protected $casts = [
        'pr_id' => 'integer',
    ];

    /**
     * Get the repository that this knowledge entry belongs to.
     */
    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    /**
     * Get the user who created this knowledge entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the feedback for this knowledge entry.
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(KnowledgeEntryFeedback::class);
    }
}