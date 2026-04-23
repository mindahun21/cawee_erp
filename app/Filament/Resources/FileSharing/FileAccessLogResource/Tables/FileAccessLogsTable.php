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
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'uploaded' => 'Uploaded',
                        'previewed' => 'Previewed',
                        'downloaded' => 'Downloaded',
                        'shared' => 'Shared',
                        'revoked' => 'Revoked',
                        'deleted' => 'Deleted',
                        'access_denied' => 'Access Denied',
                        'unlocked' => 'Unlocked',
                        default => str($state)->replace('_', ' ')->title()->toString(),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'uploaded', 'shared', 'unlocked' => 'success',
                        'downloaded' => 'info',
                        'previewed' => 'gray',
                        'revoked', 'deleted', 'access_denied' => 'danger',
                        default => 'gray',
                    })
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
                TextColumn::make('share.share_token')
                    ->label('Share Token')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),
                TextColumn::make('ip_address')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('user_agent')
                    ->limit(45)
                    ->tooltip(fn ($state) => $state)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),
                TextColumn::make('accessed_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('notes')
                    ->limit(40)
                    ->tooltip(fn ($state) => $state)
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
                        'access_denied' => 'Access Denied',
                        'unlocked' => 'Unlocked',
                    ]),
                SelectFilter::make('share.share_type')
                    ->label('Share Type')
                    ->options([
                        'public' => 'Public',
                        'staff' => 'Employee',
                        'client' => 'Client',
                    ]),
                Filter::make('denied_only')
                    ->label('Denied Only')
                    ->query(fn (Builder $query): Builder => $query->where(function (Builder $query): void {
                        $query
                            ->where('action', 'access_denied')
                            ->orWhere(fn (Builder $legacy) => $legacy
                                ->whereNotNull('notes')
                                ->where('notes', 'like', 'Denied access:%'));
                    })),
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
