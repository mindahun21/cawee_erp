<?php

namespace App\Filament\Resources\FileSharing\FileShareResource\Schemas;

use App\Models\SharedFile;
use App\Models\SharedFolder;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FileShareForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('created_by')
                ->default(fn () => auth()->id()),

            Select::make('shared_file_id')
                ->label('File')
                ->options(SharedFile::query()->orderBy('display_name')->pluck('display_name', 'id'))
                ->searchable()
                ->preload(),

            Select::make('shared_folder_id')
                ->label('Folder')
                ->options(SharedFolder::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload(),

            Select::make('share_type')
                ->options([
                    'staff' => 'Staff',
                    'client' => 'Client',
                    'public' => 'Public',
                ])
                ->default('staff')
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
                ->label('Recipient User')
                ->options(User::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload(),

            TextInput::make('shared_with_email')
                ->email(),

            TextInput::make('password')
                ->password()
                ->revealable(),

            TextInput::make('max_downloads')
                ->numeric()
                ->minValue(1),

            DateTimePicker::make('expires_at')
                ->seconds(false),

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
