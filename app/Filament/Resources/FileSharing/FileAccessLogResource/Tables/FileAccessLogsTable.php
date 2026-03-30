<?php

namespace App\Filament\Resources\FileSharing\FileAccessLogResource\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FileAccessLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('accessed_at', 'desc')
            ->columns([
                TextColumn::make('action')
                    ->badge()
                    ->sortable(),
                TextColumn::make('file.display_name')
                    ->label('File')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('share.share_type')
                    ->label('Share Type')
                    ->placeholder('-'),
                TextColumn::make('user.name')
                    ->label('Actor')
                    ->placeholder('Guest'),
                TextColumn::make('ip_address')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('accessed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('notes')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options([
                        'uploaded' => 'Uploaded',
                        'previewed' => 'Previewed',
                        'downloaded' => 'Downloaded',
                        'shared' => 'Shared',
                        'revoked' => 'Revoked',
                        'deleted' => 'Deleted',
                    ]),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
