<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('roles.view_any');
    }

    public function view(User $actor, Role $role): bool
    {
        return $actor->can('roles.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('roles.create');
    }

    public function update(User $actor, Role $role): bool
    {
        return $actor->can('roles.update') && $role->name !== 'super_admin';
    }

    public function delete(User $actor, Role $role): bool
    {
        return $actor->can('roles.delete') && $role->name !== 'super_admin';
    }
}
