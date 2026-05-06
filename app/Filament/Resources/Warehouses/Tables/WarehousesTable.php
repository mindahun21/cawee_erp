<?php

namespace App\Filament\Resources\Warehouses\Tables;

use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

class WarehousesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('warehouse_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->color('gray'),
                TextColumn::make('name')
                    ->label('Warehouse Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),
                TextColumn::make('warehouseType.name')
                    ->label('Type')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('manager.full_name')
                    ->label('Manager')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('employees_count')
                    ->label('Staff Assigned')
                    ->counts('employees')
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('city')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('country.name')
                    ->label('Country')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
                TextColumn::make('order')
                    ->label('Sort')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('city')
                    ->options(fn () => \App\Models\Warehouse::whereNotNull('city')->pluck('city', 'city')->toArray())
                    ->searchable(),
                \Filament\Tables\Filters\SelectFilter::make('country_id')
                    ->label('Country')
                    ->relationship('country', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('warehouse_type_id')
                    ->label('Type')
                    ->relationship('warehouseType', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                \Filament\Tables\Filters\TrashedFilter::make(),
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
            ->groups([
                \Filament\Tables\Grouping\Group::make('country.name')
                    ->label('Country')
                    ->collapsible(),
                \Filament\Tables\Grouping\Group::make('warehouseType.name')
                    ->label('Warehouse Type')
                    ->collapsible(),
                \Filament\Tables\Grouping\Group::make('city')
                    ->label('City')
                    ->collapsible(),
            ]);
    }
}
