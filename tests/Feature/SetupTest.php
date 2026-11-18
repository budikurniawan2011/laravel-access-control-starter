<?php

use App\Models\InstallationState;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Activitylog\Models\Activity;

test('shows the one-time setup wizard before installation', function () {
    $this->get('/setup')
        ->assertOk()
        ->assertSee('fi-simple-layout')
        ->assertSee('fi-simple-page-content')
        ->assertSee('fi-sc-has-gap')
        ->assertSee('fi-input')
        ->assertSee('fi-btn');
    $this->get('/admin')->assertRedirect('/setup');
});

test('creates the first super administrator and locks the setup wizard', function () {
    $response = $this->post('/setup', [
        'name' => 'Primary Admin',
        'email' => 'admin@example.test',
        'password' => 'StrongPassword123!',
        'password_confirmation' => 'StrongPassword123!',
    ]);

    $response->assertRedirect('/admin/login');

    $user = User::query()->where('email', 'admin@example.test')->firstOrFail();

    expect(Hash::check('StrongPassword123!', $user->password))->toBeTrue()
        ->and($user->is_active)->toBeTrue()
        ->and($user->hasRole('super_admin'))->toBeTrue()
        ->and(InstallationState::query()->whereKey(1)->value('setup_completed'))->toBeTrue();

    $this->get('/setup')->assertRedirect('/admin/login');
    $this->get('/admin/login')->assertOk();
    $this->post('/setup', [
        'name' => 'Second Admin',
        'email' => 'second@example.test',
        'password' => 'StrongPassword123!',
        'password_confirmation' => 'StrongPassword123!',
    ])->assertSessionHasErrors('setup');

    expect(User::query()->count())->toBe(1);
    expect(Activity::query()->where('description', 'user.roles_assigned')->exists())->toBeTrue();
});

test('requires a strong confirmed password during setup', function () {
    $this->from('/setup')->post('/setup', [
        'name' => 'Primary Admin',
        'email' => 'admin@example.test',
        'password' => 'short',
        'password_confirmation' => 'different',
    ])->assertRedirect('/setup')
        ->assertSessionHasErrors('password');

    expect(User::query()->exists())->toBeFalse();
    expect(InstallationState::query()->whereKey(1)->value('setup_completed'))->toBeFalse();
});

test('requires the configured setup key', function () {
    config(['app.setup_key' => 'private-setup-key']);

    $this->get('/setup')->assertOk()->assertSee('name="setup_key"', false);
    $this->post('/setup', [
        'name' => 'Primary Admin',
        'email' => 'admin@example.test',
        'password' => 'StrongPassword123!',
        'password_confirmation' => 'StrongPassword123!',
    ])->assertForbidden();

    expect(User::query()->exists())->toBeFalse();

    $this->post('/setup', [
        'setup_key' => 'private-setup-key',
        'name' => 'Primary Admin',
        'email' => 'admin@example.test',
        'password' => 'StrongPassword123!',
        'password_confirmation' => 'StrongPassword123!',
    ])->assertRedirect('/admin/login');
});

test('allows an existing non-admin user to be upgraded through initial setup', function () {
    User::factory()->create(['email' => 'member@example.test']);

    $this->get('/setup')->assertOk();
    $this->post('/setup', [
        'name' => 'Primary Admin',
        'email' => 'admin@example.test',
        'password' => 'StrongPassword123!',
        'password_confirmation' => 'StrongPassword123!',
    ])->assertRedirect('/admin/login');

    expect(User::role('super_admin')->where('is_active', true)->exists())->toBeTrue();
});
