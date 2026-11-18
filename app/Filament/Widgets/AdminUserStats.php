<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminUserStats extends StatsOverviewWidget
{
    protected static ?bool $isLazyLoaded = false;

    protected ?string $heading = 'User overview';

    public static function canView(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->hasRole('admin');
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Active users', User::query()->where('is_active', true)->count())
                ->icon(Heroicon::OutlinedCheckCircle),
            Stat::make('Inactive users', User::query()->where('is_active', false)->count())
                ->icon(Heroicon::OutlinedNoSymbol),
        ];
    }
}
