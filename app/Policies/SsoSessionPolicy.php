<?php

namespace App\Policies;

use App\Models\SsoSession;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SsoSessionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage_sessions');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SsoSession $ssoSession): bool
    {
        return $user->hasPermission('manage_sessions');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false; // Sessions are created programmatically
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SsoSession $ssoSession): bool
    {
        return $user->hasPermission('manage_sessions');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SsoSession $ssoSession): bool
    {
        return $user->hasPermission('manage_sessions');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SsoSession $ssoSession): bool
    {
        return $user->hasPermission('manage_sessions');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SsoSession $ssoSession): bool
    {
        return $user->hasPermission('manage_sessions');
    }
}
