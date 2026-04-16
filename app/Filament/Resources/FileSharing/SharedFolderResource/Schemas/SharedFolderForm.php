<?php

namespace App\Filament\Resources\FileSharing\SharedFolderResource\Schemas;

use App\Models\SharedFolder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SharedFolderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Hidden::make('owner_id')
                ->default(fn () => auth()->id()),

            Select::make('parent_id')
                ->label('Parent Folder')
                ->options(SharedFolder::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->placeholder('Root folder (optional)')
                ->helperText('Leave this empty to create a top-level folder.'),

            TextInput::make('name')
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

            Textarea::make('description')
                ->columnSpanFull(),
        ]);
    }
}
