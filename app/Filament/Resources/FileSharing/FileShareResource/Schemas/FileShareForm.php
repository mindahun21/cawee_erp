<?php

namespace App\Filament\Resources\FileSharing\FileShareResource\Schemas;

use App\Models\FileSharingSetting;
use App\Models\SharedFile;
use App\Models\SharedFolder;
use App\Support\FileSharing\EmployeeRecipientOptions;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class FileShareForm
{
    public static function configure(Schema $schema): Schema
    {
        $publicSharingEnabled = FileSharingSetting::isPublicSharingEnabled();
        $requiresPublicPassword = FileSharingSetting::requiresPublicPassword();
        $defaultExpiryDays = FileSharingSetting::defaultLinkExpiryDays();

        return $schema->components([
            Hidden::make('created_by')
                ->default(fn () => auth()->id()),

            Select::make('shared_file_id')
                ->label('File')
                ->options(SharedFile::query()->orderBy('display_name')->pluck('display_name', 'id'))
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function ($state, Set $set): void {
                    if (filled($state)) {
                        $set('shared_folder_id', null);
                    }
                })
                ->helperText('Choose a file OR a folder, not both.')
                ->required(fn (Get $get): bool => blank($get('shared_folder_id')))
                ->rule(fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get): void {
                    if (filled($value) && filled($get('shared_folder_id'))) {
                        $fail('Choose either a file or a folder, not both.');
                    }
                }),

            Select::make('shared_folder_id')
                ->label('Folder')
                ->options(SharedFolder::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function ($state, Set $set): void {
                    if (filled($state)) {
                        $set('shared_file_id', null);
                    }
                })
                ->helperText('Choose a file OR a folder, not both.')
                ->required(fn (Get $get): bool => blank($get('shared_file_id')))
                ->rule(fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get): void {
                    if (filled($value) && filled($get('shared_file_id'))) {
                        $fail('Choose either a file or a folder, not both.');
                    }
                }),

            Select::make('share_type')
                ->options(function (Get $get) use ($publicSharingEnabled): array {
                    $options = [
                        'staff' => 'Employee',
                        'client' => 'Client',
                    ];

                    if ($publicSharingEnabled || $get('share_type') === 'public') {
                        $options['public'] = 'Public';
                    }

                    return $options;
                })
                ->default('staff')
                ->live()
                ->afterStateUpdated(function ($state, Set $set): void {
                    if ($state === 'staff') {
                        $set('shared_with_email', null);
                    }

                    if ($state === 'client') {
                        $set('shared_with_user_id', null);
                    }

                    if ($state === 'public') {
                        $set('shared_with_user_id', null);
                        $set('shared_with_email', null);
                    }
                })
                ->helperText($publicSharingEnabled ? null : 'Public sharing is disabled by policy.')
                ->required(),

            Select::make('access_level')
                ->options([
                    'view' => 'View',
                    'download' => 'Download',
                    'upload' => 'Upload',
                    'manage' => 'Manage',
                ])
                ->default('download')
                ->required(),

            Select::make('shared_with_user_id')
                ->hidden(),

            Hidden::make('shared_with_employee_id')
                ->hidden(),

            Select::make('recipient_employee_id')
                ->label('Recipient Employee')
                ->options(fn (): array => EmployeeRecipientOptions::employeeOptions())
                ->searchable()
                ->preload()
                ->afterStateHydrated(function (Field $component, $state, ?\Illuminate\Database\Eloquent\Model $record): void {
                    if ($record && filled($record->shared_with_employee_id)) {
                        $component->state($record->shared_with_employee_id);
                    } elseif ($record && filled($record->shared_with_user_id)) {
                        $component->state(EmployeeRecipientOptions::employeeIdForUserId($record->shared_with_user_id));
                    }
                })
                ->afterStateUpdated(function ($state, Set $set): void {
                    $set('shared_with_employee_id', $state);
                    $set('shared_with_user_id', EmployeeRecipientOptions::userIdForEmployeeId($state));
                })
                ->helperText('All employees are listed. If an employee has no linked login yet, the share still keeps the employee recipient record.')
                ->visible(fn ($get) => $get('share_type') === 'staff')
                ->dehydrated(false)
                ->rules(['required_if:share_type,staff']),

            TextInput::make('shared_with_email')
                ->email()
                ->visible(fn ($get) => $get('share_type') === 'client')
                ->dehydrated(fn ($get) => $get('share_type') === 'client')
                ->rules(['required_if:share_type,client']),

            TextInput::make('password')
                ->password()
                ->revealable()
                ->required(fn (Get $get): bool => $get('share_type') === 'public' && $requiresPublicPassword)
                ->helperText($requiresPublicPassword ? 'Public share links must have a password.' : null),

            TextInput::make('max_downloads')
                ->numeric()
                ->minValue(1),

            DateTimePicker::make('expires_at')
                ->seconds(false)
                ->default(
                    $defaultExpiryDays > 0
                        ? now()->addDays($defaultExpiryDays)
                        : null
                ),

            Select::make('is_active')
                ->options([
                    1 => 'Active',
                    0 => 'Inactive',
                ])
                ->default(1)
                ->required(),
        ]);
    }
}
