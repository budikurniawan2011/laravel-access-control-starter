<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use App\Rules\RestrictUserRoleAssignment;
use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Users';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->minLength(12)
                    ->visible(fn (?User $record): bool => $record === null
                        || auth()->user()?->can('resetPassword', $record))
                    ->dehydrated(fn (?string $state, ?User $record): bool => filled($state)
                        && ($record === null || auth()->user()?->can('resetPassword', $record))),
                TextInput::make('password_confirmation')
                    ->password()
                    ->same('password')
                    ->required(fn ($get): bool => filled($get('password')))
                    ->dehydrated(false),
                Toggle::make('is_active')
                    ->required()
                    ->default(true)
                    ->disabled(fn (?User $record): bool => $record?->is(auth()->user())
                        && auth()->user()?->hasRole('super_admin')
                        && User::role('super_admin')->where('is_active', true)->count() <= 1),
                Select::make('roles')
                    ->visible(fn (?User $record): bool => ! $record?->hasRole('super_admin'))
                    ->relationship(
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => auth()->user()?->hasRole('super_admin')
                            ? $query->where('name', '!=', 'super_admin')
                            : $query->where('name', 'user'),
                    )
                    ->multiple()
                    ->preload()
                    ->required()
                    ->rules([new RestrictUserRoleAssignment])
                    ->helperText(fn (): string => auth()->user()?->hasRole('super_admin')
                        ? 'The super_admin role can only be assigned during initial setup.'
                        : 'Admins can only assign the user role.')
                    ->dehydrated(fn (?User $record): bool => ! $record?->hasRole('super_admin'))
                    ->disabled(fn (?User $record): bool => $record?->is(auth()->user())
                        && auth()->user()?->hasRole('super_admin')
                        && User::role('super_admin')->where('is_active', true)->count() <= 1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->badge()
                    ->separator(','),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make()
                    ->before(function (User $record): void {
                        request()->attributes->set(
                            "user_roles_before_{$record->getKey()}",
                            $record->roles()->orderBy('name')->pluck('name')->all(),
                        );
                    })
                    ->after(function (User $record): void {
                        $key = "user_roles_before_{$record->getKey()}";
                        $before = request()->attributes->get($key, []);
                        request()->attributes->remove($key);
                        $after = $record->roles()->orderBy('name')->pluck('name')->all();

                        if ($before !== $after) {
                            activity('access-control')
                                ->causedBy(auth()->user())
                                ->performedOn($record)
                                ->withProperties(['roles' => $after])
                                ->log('user.roles_updated');
                        }
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }
}
