<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Models\Role;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRoles extends ManageRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->after(function (Role $record): void {
                    activity('access-control')
                        ->causedBy(auth()->user())
                        ->performedOn($record)
                        ->withProperties(['permissions' => $record->permissions()->orderBy('name')->pluck('name')->all()])
                        ->log('role.permissions_updated');
                }),
        ];
    }
}
