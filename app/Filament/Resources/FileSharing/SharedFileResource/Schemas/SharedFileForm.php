<?php

namespace App\Filament\Resources\FileSharing\SharedFileResource\Schemas;

use App\Models\SharedFolder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SharedFileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('uploaded_by')
                ->default(fn () => auth()->id()),

            Select::make('folder_id')
                ->label('Folder')
                ->options(SharedFolder::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload(),

            TextInput::make('display_name')
                ->required()
                ->maxLength(255),

            Select::make('visibility')
                ->options([
                    'private' => 'Private',
                    'internal' => 'Internal',
                    'client' => 'Client',
                    'public' => 'Public',
                ])
                ->default('internal')
                ->required(),

            FileUpload::make('path')
                ->label('File')
                ->disk(config('filesystems.default'))
                ->directory('shared-files')
                ->downloadable()
                ->openable()
                ->preserveFilenames()
                ->storeFileNamesIn('original_name')
                ->required(),

            DateTimePicker::make('expires_at')
                ->seconds(false),

            Select::make('is_locked')
                ->label('Lock file from replacement')
                ->options([
                    0 => 'No',
                    1 => 'Yes',
                ])
                ->default(0)
                ->required(),

            Textarea::make('notes')
                ->dehydrated(false)
                ->columnSpanFull()
                ->placeholder('Reserved for future share/change notes.'),
        ]);
    }
}
