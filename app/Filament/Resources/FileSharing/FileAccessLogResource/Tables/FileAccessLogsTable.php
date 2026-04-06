<?php

namespace App\Filament\Resources\FileSharing\FileAccessLogResource\Tables;

use Filament\Tables\Filters\Filter;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
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
                SelectFilter::make('share.share_type')
                    ->label('Share Type')
                    ->options([
                        'public' => 'Public',
                        'staff' => 'Staff',
                        'client' => 'Client',
                    ]),
                Filter::make('accessed_at')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $query, $date) => $query->whereDate('accessed_at', '>=', $date))
                            ->when($data['until'] ?? null, fn (Builder $query, $date) => $query->whereDate('accessed_at', '<=', $date));
                    }),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
