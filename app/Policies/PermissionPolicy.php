<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

class PermissionPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('permissions.view_any');
    }

    public function view(User $actor, Permission $permission): bool
    {
        return $actor->can('permissions.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('permissions.create');
    }

    public function update(User $actor, Permission $permission): bool
    {
        return $actor->can('permissions.update');
    }

    public function delete(User $actor, Permission $permission): bool
    {
        return $actor->can('permissions.delete');
    }
}
