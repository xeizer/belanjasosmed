<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view the user list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can update the given user.
     */
    public function update(User $user, User $model): bool
    {
        return $user->isSuperAdmin();
    }

    /**
     * Determine whether the user can change the role of the given user.
     *
     * Super-Admins cannot change their own role, so at least one Super-Admin always remains.
     */
    public function changeRole(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && $user->isNot($model);
    }

    /**
     * Determine whether the user can delete the given user.
     *
     * Super-Admins cannot delete themselves, so at least one Super-Admin always remains.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isSuperAdmin() && $user->isNot($model);
    }
}
