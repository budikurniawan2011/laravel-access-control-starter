<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('users.view_any');
    }

    public function view(User $actor, User $user): bool
    {
        return $actor->can('users.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('users.create');
    }

    public function update(User $actor, User $user): bool
    {
        return $actor->can('users.update')
            && ($actor->hasRole('super_admin') || ! $user->hasAnyRole(['admin', 'super_admin']));
    }

    public function resetPassword(User $actor, User $user): bool
    {
        if ($actor->hasRole('super_admin')) {
            return ! $user->hasRole('super_admin') || $actor->is($user);
        }

        return $actor->can('users.update')
            && ! $user->hasAnyRole(['admin', 'super_admin']);
    }

    public function delete(User $actor, User $user): bool
    {
        if (! $actor->can('users.delete') || $actor->is($user)) {
            return false;
        }

        if ($actor->hasRole('super_admin')) {
            return ! $user->hasRole('super_admin')
                || User::role('super_admin')->where('is_active', true)->count() > 1;
        }

        return ! $user->hasAnyRole(['admin', 'super_admin']);
    }
}
