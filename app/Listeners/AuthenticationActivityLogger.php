<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class AuthenticationActivityLogger
{
    public function login(Login $event): void
    {
        $this->record('authentication.login', $event->user, $event->guard);
    }

    public function failed(Failed $event): void
    {
        $this->record('authentication.login_failed', $event->user, $event->guard);
    }

    public function logout(Logout $event): void
    {
        $this->record('authentication.logout', $event->user, $event->guard);
    }

    private function record(string $description, ?Authenticatable $user, string $guard): void
    {
        activity('authentication')
            ->causedBy($user instanceof Model ? $user : null)
            ->withProperties([
                'guard' => $guard,
            ])
            ->log($description);
    }
}
