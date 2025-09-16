<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Repository extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'github_id',
        'name',
        'owner_id',
        'webhook_secret',
    ];

    protected $casts = [
        'github_id' => 'integer',
        'owner_id' => 'integer',
    ];

    /**
     * Get the knowledge entries for this repository.
     */
    public function knowledgeEntries(): HasMany
    {
        return $this->hasMany(KnowledgeEntry::class);
    }

    /**
     * Get the organizations that this repository belongs to.
     */
    public function organisations(): BelongsToMany
    {
        return $this->belongsToMany(Organisation::class, 'organisation_repositories')
                    ->withTimestamps();
    }

    /**
     * Get the users that have access to this repository.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'repository_users')
                    ->withPivot(['role_id', 'is_admin'])
                    ->withTimestamps();
    }

    /**
     * Get the GitHub collaborators for this repository.
     */
    public function collaborators(): HasMany
    {
        return $this->hasMany(RepositoryCollaborator::class);
    }

    /**
     * Get the active GitHub collaborators for this repository.
     */
    public function activeCollaborators(): HasMany
    {
        return $this->hasMany(RepositoryCollaborator::class)->where('is_active', true);
    }
}