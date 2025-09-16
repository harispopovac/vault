<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prompt extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'field_definitions',
        'organization_id',
        'created_from_template_id',
        'created_by',
    ];

    protected $casts = [
        'field_definitions' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(PromptTemplate::class, 'created_from_template_id');
    }

    public function triggers(): HasMany
    {
        return $this->hasMany(Trigger::class);
    }
}
