<?php

namespace App\Filament\Resources\Vehicles\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;

class VehiclesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('plate_number')
                    ->label('Plate No.')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->color('gray'),
                TextColumn::make('manufacturer')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->description(fn ($record) => $record->model . ' · ' . $record->year_manufactured),
                TextColumn::make('type.name')
                    ->label('Type')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('fuel_type')
                    ->label('Fuel')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Electric' => 'success',
                        'Hybrid'   => 'info',
                        'Diesel'   => 'warning',
                        'Petrol'   => 'danger',
                        default    => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('statusRecord.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        str_contains(strtolower($state ?? ''), 'active')      => 'success',
                        str_contains(strtolower($state ?? ''), 'maintenance') => 'warning',
                        str_contains(strtolower($state ?? ''), 'repair')      => 'warning',
                        str_contains(strtolower($state ?? ''), 'out')         => 'danger',
                        str_contains(strtolower($state ?? ''), 'dispos')      => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('currentLocation.location_name')
                    ->label('Location')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('insurance_renewal_date')
                    ->label('Insurance Renewal')
                    ->date()
                    ->sortable()
                    ->color(fn ($state) => $state && $state->isPast() ? 'danger' : 'success')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('vehicle_type_id')
                    ->label('Type')
                    ->relationship('type', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('vehicle_status_id')
                    ->label('Status')
                    ->relationship('statusRecord', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('fuel_type')
                    ->options([
                        'Diesel'   => 'Diesel',
                        'Petrol'   => 'Petrol',
                        'Electric' => 'Electric',
                        'Hybrid'   => 'Hybrid',
                    ]),
                SelectFilter::make('current_location_id')
                    ->label('Location')
                    ->relationship('currentLocation', 'location_name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_active')
                    ->label('Active Status'),
                TrashedFilter::make(),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\Action::make('toggle_active')
                        ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                        ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                        ->action(fn ($record) => $record->update(['is_active' => !$record->is_active])),
                ])->icon('heroicon-m-ellipsis-vertical'),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\ForceDeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('manufacturer')
            ->groups([
                \Filament\Tables\Grouping\Group::make('type.name')
                    ->label('Vehicle Type')
                    ->collapsible(),
                \Filament\Tables\Grouping\Group::make('statusRecord.name')
                    ->label('Status')
                    ->collapsible(),
                \Filament\Tables\Grouping\Group::make('currentLocation.location_name')
                    ->label('Location')
                    ->collapsible(),
            ]);
    }
}
