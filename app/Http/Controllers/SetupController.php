<?php

namespace App\Http\Controllers;

use App\Models\InstallationState;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $this->ensureSetupTableExists();

        if (InstallationState::query()->whereKey(1)->value('setup_completed')) {
            return redirect('/admin/login');
        }

        $this->authorizeSetup($request);

        if ($this->activeSuperAdminExists()) {
            InstallationState::query()->whereKey(1)->update(['setup_completed' => true]);

            return redirect('/admin/login');
        }

        return view('setup.index');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSetupTableExists();
        $this->authorizeSetup($request);

        Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(12)->mixedCase()->letters()->numbers()->symbols(),
            ],
        ])->validate();

        DB::transaction(function () use ($request): void {
            $claimed = DB::table('installation_states')
                ->where('id', 1)
                ->where('setup_completed', false)
                ->update([
                    'setup_completed' => true,
                    'updated_at' => now(),
                ]);

            if ($claimed !== 1) {
                throw ValidationException::withMessages([
                    'setup' => 'Initial setup has already been completed.',
                ]);
            }

            if ($this->activeSuperAdminExists()) {
                throw ValidationException::withMessages([
                    'setup' => 'An active super administrator already exists. Sign in instead.',
                ]);
            }

            app(AccessControlSeeder::class)->run();

            $user = User::query()->create([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'password' => $request->string('password')->toString(),
                'is_active' => true,
            ]);

            $user->assignRole(Role::findByName('super_admin', 'web'));

            activity('access-control')
                ->causedBy($user)
                ->performedOn($user)
                ->withProperties(['roles' => ['super_admin']])
                ->log('user.roles_assigned');
        });

        return redirect('/admin/login')
            ->with('status', 'Setup complete. Sign in with your new administrator account.');
    }

    private function ensureSetupTableExists(): void
    {
        abort_unless(
            Schema::hasTable('installation_states'),
            503,
            'Run the database migrations before using the setup wizard.',
        );
    }

    private function authorizeSetup(Request $request): void
    {
        $setupKey = config('app.setup_key');

        if (is_string($setupKey) && $setupKey !== '') {
            if ($request->isMethod('GET')) {
                return;
            }

            $providedKey = $request->input('setup_key');

            abort_unless(
                is_string($providedKey) && hash_equals($setupKey, $providedKey),
                403,
                'The installation setup key is invalid.',
            );

            return;
        }

        abort_unless(
            ! app()->isProduction(),
            503,
            'Set INSTALLATION_SETUP_KEY before enabling the first-run setup wizard in production.',
        );
    }

    private function activeSuperAdminExists(): bool
    {
        return User::query()
            ->where('is_active', true)
            ->whereHas('roles', fn (Builder $query): Builder => $query
                ->where('name', 'super_admin')
                ->where('guard_name', 'web'))
            ->exists();
    }
}
