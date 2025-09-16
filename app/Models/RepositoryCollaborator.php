<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepositoryCollaborator extends Model
{
    use HasFactory;

    protected $fillable = [
        'repository_id',
        'github_user_id',
        'github_username',
        'github_avatar_url',
        'github_email',
        'permission_level',
        'vault_role_id',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'github_user_id' => 'integer',
        'vault_role_id' => 'integer',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the repository that owns this collaborator.
     */
    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    /**
     * Get the assigned Knowledge Vault role.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'vault_role_id');
    }

    /**
     * Scope a query to only include active collaborators.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by repository.
     */
    public function scopeForRepository($query, $repositoryId)
    {
        return $query->where('repository_id', $repositoryId);
    }

    /**
     * Scope a query to filter by role.
     */
    public function scopeWithRole($query, $roleId)
    {
        return $query->where('vault_role_id', $roleId);
    }

    /**
     * Scope a query to filter by permission level.
     */
    public function scopeWithPermission($query, $permission)
    {
        return $query->where('permission_level', $permission);
    }

    /**
     * Get the display name for the collaborator.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->github_username;
    }

    /**
     * Check if the collaborator has admin permissions.
     */
    public function isAdmin(): bool
    {
        return $this->permission_level === 'admin';
    }

    /**
     * Check if the collaborator can write to the repository.
     */
    public function canWrite(): bool
    {
        return in_array($this->permission_level, ['write', 'admin']);
    }
}
