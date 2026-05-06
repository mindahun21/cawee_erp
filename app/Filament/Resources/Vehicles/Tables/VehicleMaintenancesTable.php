<?php

namespace App\Filament\Resources\Vehicles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class VehicleMaintenancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('vehicle.plate_number')
                    ->label('Vehicle')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('serviceTypeRecord.name')
                    ->label('Service Type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('service_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('cost')
                    ->money('ETB')
                    ->sortable(),
                TextColumn::make('next_service_date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('vehicle_id')
                    ->label('Vehicle')
                    ->relationship('vehicle', 'plate_number')
                    ->getOptionLabelFromRecordUsing(fn (\App\Models\Vehicle $record) => $record->plate_number . ' — ' . trim("{$record->manufacturer} {$record->model}"))
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('service_type_id')
                    ->label('Service Type')
                    ->relationship('serviceTypeRecord', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\Filter::make('service_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('service_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('service_date', '<=', $date));
                    }),
                \Filament\Tables\Filters\Filter::make('cost')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('min_cost')->numeric()->label('Min Cost (ETB)'),
                        \Filament\Forms\Components\TextInput::make('max_cost')->numeric()->label('Max Cost (ETB)'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['min_cost'], fn ($q, $cost) => $q->where('cost', '>=', $cost))
                            ->when($data['max_cost'], fn ($q, $cost) => $q->where('cost', '<=', $cost));
                    }),
                TrashedFilter::make(),
            ])
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
