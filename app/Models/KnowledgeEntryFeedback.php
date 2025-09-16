<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeEntryFeedback extends Model
{
    use HasFactory;

    protected $table = 'knowledge_entry_feedback';

    protected $fillable = [
        'knowledge_entry_id',
        'user_id',
        'rating',
        'comment',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Get the knowledge entry that this feedback belongs to.
     */
    public function knowledgeEntry(): BelongsTo
    {
        return $this->belongsTo(KnowledgeEntry::class);
    }

    /**
     * Get the user who provided this feedback.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}