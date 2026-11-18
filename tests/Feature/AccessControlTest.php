<?php

use App\Models\Role;
use App\Models\InstallationState;
use App\Models\Permission;
use App\Models\User;
use App\Rules\RestrictUserRoleAssignment;
use App\Filament\Widgets\AccessControlStats;
use App\Filament\Widgets\AdminUserStats;
use App\Filament\Resources\Activities\ActivityResource;
use App\Policies\UserPolicy;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Spatie\Activitylog\Models\Activity;

test('seeds the initial roles and least-privilege admin permissions', function () {
    $this->seed();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    expect(Gate::forUser($admin)->allows('viewAny', User::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('viewAny', Role::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('create', Role::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('viewAny', Activity::class))->toBeTrue();
});

test('only super administrators can manage role and permission definitions', function () {
    $this->seed();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $userRole = Role::findByName('user', 'web');
    $permission = Permission::query()->firstOrFail();

    expect(Gate::forUser($admin)->allows('create', Role::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('update', $userRole))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('create', Permission::class))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('update', $permission))->toBeFalse()
        ->and(Gate::forUser($superAdmin)->allows('create', Role::class))->toBeTrue()
        ->and(Gate::forUser($superAdmin)->allows('update', $userRole))->toBeTrue()
        ->and(Gate::forUser($superAdmin)->allows('create', Permission::class))->toBeTrue()
        ->and(Gate::forUser($superAdmin)->allows('update', $permission))->toBeTrue();
});

test('does not let an admin delete a super administrator', function () {
    $this->seed();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    expect(Gate::forUser($admin)->allows('delete', $superAdmin))->toBeFalse();
});

test('regular admins can only edit and delete regular users', function () {
    $this->seed();

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $otherAdmin = User::factory()->create();
    $otherAdmin->assignRole('admin');

    $regularUser = User::factory()->create();
    $regularUser->assignRole('user');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $policy = app(UserPolicy::class);

    expect($policy->update($admin, $regularUser))->toBeTrue()
        ->and($policy->delete($admin, $regularUser))->toBeTrue()
        ->and($policy->resetPassword($admin, $regularUser))->toBeTrue()
        ->and($policy->update($admin, $otherAdmin))->toBeFalse()
        ->and($policy->delete($admin, $otherAdmin))->toBeFalse()
        ->and($policy->resetPassword($admin, $otherAdmin))->toBeFalse()
        ->and($policy->update($admin, $superAdmin))->toBeFalse()
        ->and($policy->delete($admin, $superAdmin))->toBeFalse()
        ->and($policy->resetPassword($admin, $superAdmin))->toBeFalse()
        ->and($policy->update($superAdmin, $otherAdmin))->toBeTrue()
        ->and($policy->delete($superAdmin, $otherAdmin))->toBeTrue()
        ->and($policy->resetPassword($superAdmin, $otherAdmin))->toBeTrue()
        ->and($policy->resetPassword($superAdmin, $superAdmin))->toBeTrue();
});

test('does not let the only super administrator delete their own account', function () {
    $this->seed();

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    expect(app(UserPolicy::class)->delete($superAdmin, $superAdmin))->toBeFalse();
});

test('restricts user role assignment based on the acting role', function () {
    $this->seed();

    $superAdminRole = Role::findByName('super_admin', 'web');
    $adminRole = Role::findByName('admin', 'web');
    $userRole = Role::findByName('user', 'web');
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin);

    expect(Validator::make(
        ['roles' => [$userRole->getKey()]],
        ['roles' => [new RestrictUserRoleAssignment]],
    )->fails())->toBeFalse()
        ->and(Validator::make(
            ['roles' => [$adminRole->getKey()]],
            ['roles' => [new RestrictUserRoleAssignment]],
        )->fails())->toBeTrue()
        ->and(Validator::make(
            ['roles' => [$superAdminRole->getKey()]],
            ['roles' => [new RestrictUserRoleAssignment]],
        )->fails())->toBeTrue();

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');
    $this->actingAs($superAdmin);

    expect(Validator::make(
        ['roles' => [$userRole->getKey()]],
        ['roles' => [new RestrictUserRoleAssignment]],
    )->fails())->toBeFalse()
        ->and(Validator::make(
            ['roles' => [$adminRole->getKey()]],
            ['roles' => [new RestrictUserRoleAssignment]],
        )->fails())->toBeFalse()
        ->and(Validator::make(
            ['roles' => [$superAdminRole->getKey()]],
            ['roles' => [new RestrictUserRoleAssignment]],
        )->fails())->toBeTrue();
});

test('does not record passwords in user activity changes', function () {
    $this->seed();

    $user = User::factory()->create();
    $user->update([
        'email' => 'updated@example.test',
        'password' => 'AnotherStrongPassword123!',
    ]);

    $activity = Activity::query()->where('log_name', 'users')->latest('id')->firstOrFail();

    expect($activity->attribute_changes->toJson())->not->toContain('password')
        ->and($activity->attribute_changes->toJson())->not->toContain('AnotherStrongPassword123!');
});

test('logs authentication events without storing submitted credentials', function () {
    $user = User::factory()->create();

    event(new Login('web', $user, false));
    event(new Failed('web', null, [
        'email' => 'private@example.test',
        'password' => 'DoNotLogThisPassword123!',
    ]));

    $login = Activity::query()->where('description', 'authentication.login')->firstOrFail();
    $failed = Activity::query()->where('description', 'authentication.login_failed')->firstOrFail();

    expect($login->causer_id)->toBe($user->getKey())
        ->and($failed->properties->toJson())->not->toContain('private@example.test')
        ->and($failed->properties->toJson())->not->toContain('DoNotLogThisPassword123!');
});

test('formats activity properties whether returned as json or a collection', function () {
    expect(ActivityResource::formatProperties('{"guard":"web"}'))->toBe('{"guard":"web"}')
        ->and(ActivityResource::formatProperties(collect(['guard' => 'web'])))->toBe('{"guard":"web"}')
        ->and(ActivityResource::formatProperties(null))->toBe('');
});

test('allows authorized administrators to open management resources', function () {
    $this->seed();

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    InstallationState::query()->whereKey(1)->update(['setup_completed' => true]);

    $this->actingAs($admin)->get('/admin/users')->assertOk();
    $this->actingAs($admin)->get('/admin/roles')->assertOk();
    $this->actingAs($admin)->get('/admin/permissions')->assertOk();
    $this->actingAs($admin)->get('/admin/activities')->assertOk();
});

test('shows access control statistics on the dashboard for super administrators', function () {
    $this->seed();

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');
    InstallationState::query()->whereKey(1)->update(['setup_completed' => true]);
    $this->actingAs($superAdmin);

    expect(AccessControlStats::canView())->toBeTrue();

    $this->get('/admin')
        ->assertOk()
        ->assertSee('Access control overview')
        ->assertSee('Users')
        ->assertSee('Roles')
        ->assertSee('Permissions')
        ->assertSee((string) User::query()->count())
        ->assertSee((string) Role::query()->count())
        ->assertSee((string) Permission::query()->count());
});

test('shows active and inactive user counts on the admin dashboard', function () {
    $this->seed();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    User::factory()->count(2)->create(['is_active' => true]);
    User::factory()->count(2)->create(['is_active' => false]);
    InstallationState::query()->whereKey(1)->update(['setup_completed' => true]);
    $this->actingAs($admin);

    expect(AdminUserStats::canView())->toBeTrue();

    $this->get('/admin')
        ->assertOk()
        ->assertSee('User overview')
        ->assertSee('Active users')
        ->assertSee('Inactive users')
        ->assertSee('3')
        ->assertSee('2');
});

test('does not show admin user statistics to regular users or super administrators', function () {
    $this->seed();

    $user = User::factory()->create();
    $user->assignRole('user');
    $this->actingAs($user);

    expect(AdminUserStats::canView())->toBeFalse();

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');
    $this->actingAs($superAdmin);

    expect(AdminUserStats::canView())->toBeFalse();
});

test('does not show access control statistics to regular administrators', function () {
    $this->seed();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    InstallationState::query()->whereKey(1)->update(['setup_completed' => true]);
    $this->actingAs($admin);

    expect(AccessControlStats::canView())->toBeFalse();

    $this->get('/admin')
        ->assertOk()
        ->assertDontSee('Access control overview');
});

test('restricts permission management from regular admins', function () {
    $this->seed();

    $admin = User::factory()->create();
    $admin->assignRole('admin');
    InstallationState::query()->whereKey(1)->update(['setup_completed' => true]);

    $this->actingAs($admin)->get('/admin/users')->assertOk();
    $this->actingAs($admin)->get('/admin/permissions')->assertForbidden();
});
