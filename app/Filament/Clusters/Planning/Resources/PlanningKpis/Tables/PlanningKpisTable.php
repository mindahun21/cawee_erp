<?php

namespace App\Filament\Clusters\Planning\Resources\PlanningKpis\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PlanningKpisTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('indicator_name')
                    ->label('KPI Indicator')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('plan.title')
                    ->label('Linked Plan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('target_value')
                    ->numeric()
                    ->sortable()
                    ->alignment('right'),
                TextColumn::make('actual_value')
                    ->numeric()
                    ->sortable()
                    ->alignment('right')
                    ->color(fn ($record) => $record->actual_value >= $record->target_value ? 'success' : 'danger'),
                TextColumn::make('unit')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('variance')
                    ->label('Variance')
                    ->getStateUsing(fn ($record) => $record->actual_value - $record->target_value)
                    ->numeric()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->icon(fn ($state) => $state >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down'),
                TextColumn::make('department.name')
                    ->toggleable()
                    ->searchable(),
            ])
            ->filters([
                TrashedFilter::make(),
                \Filament\Tables\Filters\SelectFilter::make('department_id')
                    ->relationship('department', 'name')
                    ->label('Department')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\Filter::make('underperforming')
                    ->query(fn (Builder $query) => $query->whereRaw('actual_value < target_value'))
                    ->label('Underperforming Only'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
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
