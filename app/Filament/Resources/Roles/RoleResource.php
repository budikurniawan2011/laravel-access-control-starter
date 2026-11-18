<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\ManageRoles;
use App\Models\Role;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Roles';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Hidden::make('guard_name')
                    ->default('web'),
                Select::make('permissions')
                    ->relationship(titleAttribute: 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('permissions.name')
                    ->badge()
                    ->separator(','),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
            ])
            ->recordActions([
                EditAction::make()
                    ->before(function (Role $record): void {
                        request()->attributes->set(
                            "role_permissions_before_{$record->getKey()}",
                            $record->permissions()->orderBy('name')->pluck('name')->all(),
                        );
                    })
                    ->after(function (Role $record): void {
                        $key = "role_permissions_before_{$record->getKey()}";
                        $before = request()->attributes->get($key, []);
                        request()->attributes->remove($key);
                        $after = $record->permissions()->orderBy('name')->pluck('name')->all();

                        if ($before !== $after) {
                            activity('access-control')
                                ->causedBy(auth()->user())
                                ->performedOn($record)
                                ->withProperties(['permissions' => $after])
                                ->log('role.permissions_updated');
                        }
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRoles::route('/'),
        ];
    }
}
