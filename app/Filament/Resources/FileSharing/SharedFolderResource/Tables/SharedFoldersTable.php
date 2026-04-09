<?php

namespace App\Filament\Resources\FileSharing\SharedFolderResource\Tables;

use App\Models\SharedFolder;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SharedFoldersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('path_label')
                    ->label('Path')
                    ->state(fn (SharedFolder $record): string => $record->pathLabel())
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('parent.name')
                    ->label('Parent')
                    ->placeholder('Root'),
                TextColumn::make('children_count')
                    ->label('Subfolders')
                    ->counts('children'),
                TextColumn::make('visibility')
                    ->badge()
                    ->sortable(),
                TextColumn::make('files_count')
                    ->label('Files')
                    ->counts('files'),
                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->placeholder('-'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('hierarchy')
                    ->label('Folder Level')
                    ->options([
                        'root' => 'Root folders',
                        'nested' => 'Nested folders',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'root' => $query->whereNull('parent_id'),
                            'nested' => $query->whereNotNull('parent_id'),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                Action::make('addSubfolder')
                    ->label('Add Subfolder')
                    ->icon('heroicon-o-folder-plus')
                    ->color('info')
                    ->schema([
                        Hidden::make('parent_id')
                            ->default(fn (SharedFolder $record): int => $record->id),
                        Hidden::make('owner_id')
                            ->default(fn () => auth()->id()),
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
                            ->default(fn (SharedFolder $record): string => $record->visibility)
                            ->required(),
                        Textarea::make('description')
                            ->columnSpanFull(),
                    ])
                    ->action(function (array $data, SharedFolder $record): void {
                        SharedFolder::query()->create([
                            'parent_id' => $record->id,
                            'owner_id' => auth()->id(),
                            'name' => $data['name'],
                            'visibility' => $data['visibility'],
                            'description' => $data['description'] ?? null,
                        ]);
                    })
                    ->successNotificationTitle('Subfolder created'),
                Action::make('download_zip')
                    ->label('Download ZIP')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('warning')
                    ->url(fn (SharedFolder $record): string => route('file-sharing.folders.download', $record))
                    ->openUrlInNewTab(),
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
