<?php

namespace App\Filament\Clusters\Planning\Resources\Plans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Actions\Action;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'annual' => 'primary',
                        'monthly' => 'success',
                        'weekly' => 'warning',
                        'activity' => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('progress_percentage')
                    ->label('Progress')
                    ->numeric()
                    ->suffix('%')
                    ->color(fn (int $state): string => match (true) {
                        $state >= 100 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('parent.title')
                    ->label('Parent')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('department.name')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('project.name')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'active' => 'success',
                        'completed' => 'primary',
                        'cancelled' => 'danger',
                    }),
            ])
            ->filters([
                TrashedFilter::make(),
                \Filament\Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'annual' => 'Annual',
                        'monthly' => 'Monthly',
                        'weekly' => 'Weekly',
                        'activity' => 'Activity',
                    ]),
                \Filament\Tables\Filters\SelectFilter::make('department_id')
                    ->relationship('department', 'name')
                    ->label('Department')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('approve')
                    ->action(fn ($record) => $record->update(['status' => 'active']))
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->hidden(fn ($record) => $record->status !== 'draft'),
            ])
            ->headerActions([
                Action::make('exportExcel')
                    ->label('Export Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->action(fn () => \Filament\Notifications\Notification::make()->title('Exporting...')->send()),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
