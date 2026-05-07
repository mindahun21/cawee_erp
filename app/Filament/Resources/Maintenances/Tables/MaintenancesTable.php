<?php

namespace App\Filament\Resources\Maintenances\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

class MaintenancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('asset.name')
                    ->label('Asset / Vehicle')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->description(fn ($record) => "Total Records: " . ($record->asset?->maintenances()->count() ?? 0)),
                TextColumn::make('asset.asset_tag')
                    ->label('Serial / Plate')
                    ->formatStateUsing(function ($state, $record) {
                        if (str_starts_with($state ?? '', 'VEH-')) {
                            // Extract plate from name or asset tag
                            return str_replace(['VEH-', '(', ')'], '', $state);
                        }
                        return $record->asset?->serial_number ?: $state;
                    })
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono'),
                TextColumn::make('asset.location.name')
                    ->label('Location')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('maintenanceType.name')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'Routine', 'Preventative', 'Vehicle Service' => 'success',
                        'Repair' => 'warning',
                        'Emergency' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('statusRecord.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'Scheduled', 'main' => 'info',
                        'In Progress' => 'warning',
                        'Completed' => 'success',
                        'Cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('completion_date')
                    ->date()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notes')
                    ->label('Remarks')
                    ->formatStateUsing(fn ($state) => trim(preg_replace('/V-(MAINT|REC)-[^\s\n]*/', '', $state)))
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_warranty_improvement')
                    ->label('Warranty')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_cost')
                    ->label('Total Cost')
                    ->state(function ($record) {
                        $maintenances = $record->asset?->maintenances()->with('currency')->get();
                        if (!$maintenances) return 0;
                        
                        return $maintenances->sum(function ($m) {
                            return (float) $m->cost * (float) ($m->currency?->exchange_rate ?? 1);
                        });
                    })
                    ->money('ETB')
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),

                TextColumn::make('cost')
                    ->label('Recent Cost')
                    ->state(function ($record) {
                        return (float) $record->cost * (float) ($record->currency?->exchange_rate ?? 1);
                    })
                    ->money('ETB')
                    ->sortable()
                    ->color('info'),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('asset_id')
                    ->label('Asset / Vehicle')
                    ->relationship('asset', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('maintenance_type_id')
                    ->label('Maintenance Type')
                    ->relationship('maintenanceType', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('status_id')
                    ->label('Status')
                    ->relationship('statusRecord', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\Filter::make('start_date')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('start_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('start_date', '<=', $date));
                    }),
                \Filament\Tables\Filters\Filter::make('cost')
                    ->form([
                        TextInput::make('min_cost')->numeric()->label('Min Cost'),
                        TextInput::make('max_cost')->numeric()->label('Max Cost'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['min_cost'], fn ($q, $cost) => $q->where('cost', '>=', $cost))
                            ->when($data['max_cost'], fn ($q, $cost) => $q->where('cost', '<=', $cost));
                    }),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->actions([
                // Dynamic Quick Actions
                Action::make('quick_start')
                    ->label('Start')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status_id, [1, 2])) // main or Scheduled
                    ->action(fn ($record) => $record->update(['status_id' => 3])),

                Action::make('quick_complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status_id == 3) // In Progress
                    ->action(fn ($record) => $record->update(['status_id' => 4])),

                ActionGroup::make([
                    Action::make('view_history')
                        ->label('History')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->url(fn ($record) => \App\Filament\Resources\Maintenances\MaintenanceResource::getUrl('history', ['assetId' => $record->asset_id])),
                    Action::make('cant_fix')
                        ->label('Can\'t be fixed')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->update(['status_id' => 6])),
                    Action::make('restart')
                        ->label('Set In Progress')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->update(['status_id' => 3])),
                ])->icon('heroicon-m-ellipsis-vertical'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->groups([
                \Filament\Tables\Grouping\Group::make('asset.name')
                    ->label('Asset / Vehicle')
                    ->collapsible(),
                \Filament\Tables\Grouping\Group::make('maintenanceType.name')
                    ->label('Maintenance Type')
                    ->collapsible(),
                \Filament\Tables\Grouping\Group::make('statusRecord.name')
                    ->label('Status')
                    ->collapsible(),
            ]);
    }
}
