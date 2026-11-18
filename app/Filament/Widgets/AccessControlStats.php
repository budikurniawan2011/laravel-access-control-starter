<?php

namespace App\Filament\Widgets;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AccessControlStats extends StatsOverviewWidget
{
    protected static ?bool $isLazyLoaded = false;

    protected ?string $heading = 'Access control overview';

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasRole('super_admin');
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Users', User::query()->count())
                ->icon(Heroicon::OutlinedUsers),
            Stat::make('Roles', Role::query()->count())
                ->icon(Heroicon::OutlinedShieldCheck),
            Stat::make('Permissions', Permission::query()->count())
                ->icon(Heroicon::OutlinedKey),
        ];
    }
}
