<?php

namespace App\Rules;

use App\Models\Role;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class RestrictUserRoleAssignment implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        $roleNames = Role::query()
            ->where('name', 'super_admin')
            ->whereIn('id', $value)
            ->pluck('name');

        if ($roleNames->isNotEmpty()) {
            $fail('The super_admin role can only be assigned during initial setup.');

            return;
        }

        if (! auth()->user()?->hasRole('super_admin')) {
            $roleNames = Role::query()
                ->whereKey($value)
                ->pluck('name');

            if ($roleNames->contains(fn (string $name): bool => $name !== 'user')) {
                $fail('Admins can only assign the user role.');
            }
        }
    }
}
