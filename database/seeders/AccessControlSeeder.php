<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AccessControlSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const PERMISSIONS = [
        'access_panel',
        'users.view_any',
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'roles.view_any',
        'roles.view',
        'roles.create',
        'roles.update',
        'roles.delete',
        'permissions.view_any',
        'permissions.view',
        'permissions.create',
        'permissions.update',
        'permissions.delete',
        'activity_logs.view_any',
        'activity_logs.view',
    ];

    public function run(): void
    {
        $permissions = collect(self::PERMISSIONS)
            ->map(fn (string $name): Permission => Permission::query()->firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]));

        $superAdmin = Role::findOrCreate('super_admin', 'web');
        $superAdmin->givePermissionTo($permissions);

        $admin = Role::findOrCreate('admin', 'web');
        $admin->givePermissionTo($permissions->filter(
            fn (Permission $permission): bool => in_array($permission->name, [
                'access_panel',
                'users.view_any',
                'users.view',
                'users.create',
                'users.update',
                'users.delete',
                'roles.view_any',
                'roles.view',
                'activity_logs.view_any',
                'activity_logs.view',
            ], true),
        ));

        Role::findOrCreate('user', 'web');
    }
}
