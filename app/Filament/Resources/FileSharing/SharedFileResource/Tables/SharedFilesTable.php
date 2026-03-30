<?php

namespace App\Filament\Resources\FileSharing\SharedFileResource\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SharedFilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('folder.name')
                    ->label('Folder')
                    ->placeholder('Unfiled')
                    ->searchable(),
                TextColumn::make('original_name')
                    ->label('Stored Name')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('visibility')
                    ->badge()
                    ->sortable(),
                TextColumn::make('version_no')
                    ->label('Version')
                    ->sortable(),
                TextColumn::make('size_bytes')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => number_format(((int) $state) / 1024, 2).' KB'),
                IconColumn::make('is_locked')
                    ->boolean()
                    ->label('Locked'),
                TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->placeholder('-'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
