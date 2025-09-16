<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Get the collaborators that have this role.
     */
    public function collaborators(): HasMany
    {
        return $this->hasMany(RepositoryCollaborator::class, 'vault_role_id');
    }

    /**
     * Get the users that have this role.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
    }
}
