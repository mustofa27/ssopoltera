<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'microsoft_id',
        'avatar',
        'department',
        'job_title',
        'user_type',
        'employee_type',
        'nip',
        'nrp',
        'is_active',
        'last_login_at',
        'failed_login_attempts',
        'locked_until',
        'password_changed_at',
        'microsoft_synced_at',
        'microsoft_sync_error',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'password_changed_at' => 'datetime',
            'microsoft_synced_at' => 'datetime',
        ];
    }

    /**
     * Get the organizational affiliations for the user.
     */
    public function affiliations(): HasMany
    {
        return $this->hasMany(UserAffiliation::class);
    }

    /**
     * Get the primary affiliation for the user.
     */
    public function primaryAffiliation(): HasOne
    {
        return $this->hasOne(UserAffiliation::class)->where('is_primary', true);
    }

    /**
     * Get the roles assigned to the user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * Get the applications the user has access to.
     */
    public function applications(): Collection
    {
        $this->loadMissing('roles.applications');

        return $this->roles
            ->flatMap(fn (Role $role) => $role->applications)
            ->filter(fn (Application $application) => $application->allowsUserType($this->user_type))
            ->unique('id')
            ->values();
    }

    /**
     * Get the SSO sessions for the user.
     */
    public function ssoSessions()
    {
        return $this->hasMany(SsoSession::class);
    }

    /**
     * Get the primary identifier for the user.
     */
    public function identifier(): ?string
    {
        return $this->nip ?: $this->nrp;
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('slug', $roleName)->exists();
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('slug', $roles)->exists();
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has access to a specific application.
     */
    public function hasAccessToApplication(int $applicationId): bool
    {
        $application = Application::query()->find($applicationId);

        if (! $application) {
            return false;
        }

        return $application->isAccessibleToUser($this);
    }
}
