<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $target): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->isAdmin()) {
            return $target->role === Role::User;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admins can edit `user` accounts only. Super-admins can edit users and
     * admins, but not other super-admins, and not themselves (use Settings).
     */
    public function update(User $user, User $target): bool
    {
        if ($user->id === $target->id) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return $target->role !== Role::SuperAdmin;
        }

        if ($user->isAdmin()) {
            return $target->role === Role::User;
        }

        return false;
    }

    public function delete(User $user, User $target): bool
    {
        return $this->update($user, $target);
    }

    public function toggleActive(User $user, User $target): bool
    {
        return $this->update($user, $target);
    }

    /**
     * Role changes are super-admin-only. The new role can never be
     * `super_admin`, and we never modify an existing super-admin's role.
     */
    public function changeRole(User $user, User $target, Role $newRole): bool
    {
        if ($user->id === $target->id) {
            return false;
        }

        if (! $user->isSuperAdmin()) {
            return false;
        }

        if ($target->role === Role::SuperAdmin || $newRole === Role::SuperAdmin) {
            return false;
        }

        return true;
    }
}
