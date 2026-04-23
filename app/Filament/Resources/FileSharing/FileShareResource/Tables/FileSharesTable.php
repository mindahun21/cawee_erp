<?php

namespace App\Filament\Resources\FileSharing\FileShareResource\Tables;

use App\Support\FileSharing\EmployeeRecipientOptions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FileSharesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('file.display_name')
                    ->label('File')
                    ->searchable()
                    ->placeholder('Folder share'),
                TextColumn::make('folder.name')
                    ->label('Folder')
                    ->placeholder('File share'),
                TextColumn::make('share_type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'staff' => 'Employee',
                        'client' => 'Client',
                        'public' => 'Public',
                        default => ucfirst($state),
                    })
                    ->sortable(),
                TextColumn::make('access_level')
                    ->badge()
                    ->sortable(),
                TextColumn::make('shared_with_email')
                    ->label('Recipient Email')
                    ->placeholder('-'),
                TextColumn::make('shared_with_employee_id')
                    ->label('Recipient Employee')
                    ->formatStateUsing(fn ($state, $record): string => EmployeeRecipientOptions::labelForEmployee($record->employeeRecipient) ?? EmployeeRecipientOptions::labelForUser($record->recipient) ?? '-')
                    ->placeholder('-'),
                TextColumn::make('share_url')
                    ->label('Share URL')
                    ->copyable()
                    ->tooltip(fn ($state) => $state)
                    ->url(fn ($record) => $record->share_url, true)
                    ->openUrlInNewTab()
                    ->wrap(),
                TextColumn::make('download_count')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->placeholder('No expiry'),
            ])
            ->filters([
                SelectFilter::make('share_type')
                    ->options([
                        'staff' => 'Employee',
                        'client' => 'Client',
                        'public' => 'Public',
                    ]),
            ])
            ->recordActions([
                EditAction::make('edit')
                    ->successNotificationTitle('Share updated successfully'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make('deleteBulk'),
                ]),
            ]);
    }
}
