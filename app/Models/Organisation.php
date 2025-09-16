<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organisation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
    ];

    /**
     * Get the users that belong to this organisation.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'organisation_users')
                    ->withTimestamps();
    }

    /**
     * Get the organisation users pivot records.
     */
    public function organisationUsers(): HasMany
    {
        return $this->hasMany(OrganisationUser::class);
    }

    /**
     * Get the repositories that belong to this organisation.
     */
    public function repositories(): BelongsToMany
    {
        return $this->belongsToMany(Repository::class, 'organisation_repositories')
                    ->withTimestamps();
    }
}