<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'client_id',
        'client_secret',
        'redirect_uri',
        'logout_uri',
        'logo',
        'is_active',
        'allowed_scopes',
        'allowed_user_types',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'allowed_scopes' => 'array',
        'allowed_user_types' => 'array',
    ];

    protected $hidden = [
        'client_secret',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($application) {
            if (empty($application->slug)) {
                $application->slug = Str::slug($application->name);
            }
            if (empty($application->client_id)) {
                $application->client_id = Str::uuid();
            }
            if (empty($application->client_secret)) {
                $application->client_secret = Str::random(64);
            }
        });
    }

    /**
     * Get the roles that have access to this application.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot('granted_at')
            ->withTimestamps();
    }

    /**
     * Get effective users through assigned roles.
     */
    public function accessibleUsers(): Collection
    {
        $this->loadMissing('roles.users');

        return $this->roles
            ->flatMap(fn (Role $role) => $role->users)
            ->filter(fn (User $user) => $this->allowsUserType($user->user_type))
            ->unique('id')
            ->values();
    }

    public function allowsUserType(?string $userType): bool
    {
        $allowedUserTypes = collect($this->allowed_user_types ?? [])
            ->filter()
            ->values();

        if ($allowedUserTypes->isEmpty()) {
            return true;
        }

        if ($userType === null || $userType === '') {
            return false;
        }

        return $allowedUserTypes->contains($userType);
    }

    public function isAccessibleToUser(User $user): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if (! $this->allowsUserType($user->user_type)) {
            return false;
        }

        return $user->roles()
            ->whereIn('roles.id', $this->roles()->pluck('roles.id'))
            ->exists();
    }

    /**
     * Get the SSO sessions for this application.
     */
    public function ssoSessions(): HasMany
    {
        return $this->hasMany(SsoSession::class);
    }
}
