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
                    ->color('primary'),
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
                    ->sortable(),
                TextColumn::make('maintenanceType.name')
                    ->label('Type')
                    ->sortable(),
                TextColumn::make('statusRecord.name')
                    ->label('Status')
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('completion_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Remarks')
                    ->limit(30)
                    ->toggleable(),
                IconColumn::make('is_warranty_improvement')
                    ->label('Warranty')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                TextColumn::make('cost')
                    ->money('USD')
                    ->sortable(),
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
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
