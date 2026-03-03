<?php

namespace App\Filament\Resources\UserManagement;

use App\Filament\Resources\UserManagement\Pages\CreateUser;
use App\Filament\Resources\UserManagement\Pages\EditUser;
use App\Filament\Resources\UserManagement\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'System Administration';

    protected static ?string $navigationLabel = 'Users & Roles';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Account Information')->columns(2)->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(150),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(User::class, 'email', ignoreRecord: true)
                    ->maxLength(150),

                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation) => $operation === 'create')
                    ->label(fn (string $operation) => $operation === 'create' ? 'Password' : 'New Password (leave blank to keep current)')
                    ->minLength(8)
                    ->maxLength(100)
                    ->columnSpanFull(),
            ]),

            Section::make('Roles & Permissions')
                ->description('Assign one or more roles to this user. Roles control what the user can see and do in the system.')
                ->schema([
                    Select::make('roles')
                        ->label('Assigned Roles')
                        ->multiple()
                        ->relationship('roles', 'name')
                        ->options(
                            Role::all()->pluck('name', 'id')->map(fn ($name) => match ($name) {
                                'super_admin'   => '🔴 Super Admin — Full system access',
                                'hr_director'   => '🟠 HR Director — Final approvals + full HR management',
                                'hr_officer'    => '🟡 HR Officer — HR approvals + employee management',
                                'hr_supervisor' => '🟢 HR Supervisor — Supervisor-level approvals',
                                'hr_staff'      => '🔵 HR Staff — Read-only access',
                                default         => $name,
                            })
                        )
                        ->preload()
                        ->searchable()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()->sortable()->weight('semibold'),

                TextColumn::make('email')
                    ->searchable()->sortable()->copyable(),

                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'super_admin'   => 'danger',
                        'hr_director'   => 'warning',
                        'hr_officer'    => 'info',
                        'hr_supervisor' => 'success',
                        'hr_staff'      => 'gray',
                        default         => 'gray',
                    })
                    ->separator(', '),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('roles')
                    ->label('Role')
                    ->relationship('roles', 'name')
                    ->options(
                        Role::all()->pluck('name', 'id')
                    ),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit'   => EditUser::route('/{record}/edit'),
        ];
    }
}
