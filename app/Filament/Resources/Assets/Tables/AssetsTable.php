<?php

namespace App\Filament\Resources\Assets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ExportAction;
use Filament\Tables\Table;
use Filament\Tables\Enums\FiltersLayout;
use App\Filament\Exports\AssetExporter;
use Filament\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;

class AssetsTable
{
    public static function configure(Table $table, string $resource = \App\Filament\Resources\Assets\AssetResource::class): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\ImageColumn::make('image')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl('https://ui-avatars.com/api/?name=Asset&color=7F9CF5&background=EBF4FF')
                    ->toggleable(),

                \Filament\Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->wrap(),

                \Filament\Tables\Columns\TextColumn::make('assetModel.category.name')
                    ->label('Category')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                \Filament\Tables\Columns\TextColumn::make('assetModel.type.name')
                    ->label('Type')
                    ->sortable()
                    ->toggleable(),

                \Filament\Tables\Columns\TextColumn::make('statusRecord.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'Available'   => 'success',
                        'Assigned'    => 'info',
                        'Maintenance' => 'warning',
                        'Disposed'    => 'danger',
                        'Lost'        => 'gray',
                        default       => 'gray',
                    })
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('quantity')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                \Filament\Tables\Columns\TextColumn::make('location.location_name')
                    ->label('Location')
                    ->sortable(),

                \Filament\Tables\Columns\TextColumn::make('purchase_cost')
                    ->money('INR')
                    ->sortable()
                    ->toggleable(),

                \Filament\Tables\Columns\TextColumn::make('barcode')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                \Filament\Tables\Columns\TextColumn::make('assetModel.manufacturer.name')
                    ->label('Manufacturer')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                \Filament\Tables\Columns\TextColumn::make('conditionRecord.name')
                    ->label('Condition')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('asset_status_id')
                    ->label('Status')
                    ->relationship('statusRecord', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('category')
                    ->label('Category')
                    ->relationship('assetModel.category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('assetType')
                    ->label('Asset Type')
                    ->relationship('assetModel.type', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_fixed_asset')
                    ->label('Fixed Asset?'),
            ])
            ->filtersLayout(FiltersLayout::Modal)
            ->filtersFormColumns(2)
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make()
                        ->url(fn ($record) => 
                            str_starts_with($record->asset_tag ?? '', 'VEH-')
                            ? \App\Filament\Resources\VehicleManagement\Vehicles\VehicleResource::getUrl('edit', ['record' => (int) str_replace('VEH-', '', $record->asset_tag)])
                            : $resource::getUrl('view', ['record' => $record])
                        ),
                    \Filament\Actions\EditAction::make()
                        ->url(fn ($record) => 
                            str_starts_with($record->asset_tag ?? '', 'VEH-')
                            ? \App\Filament\Resources\VehicleManagement\Vehicles\VehicleResource::getUrl('edit', ['record' => (int) str_replace('VEH-', '', $record->asset_tag)])
                            : $resource::getUrl('edit', ['record' => $record])
                        ),
                    \Filament\Actions\DeleteAction::make(),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->tooltip('Actions')
                ->color('gray'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                ExportAction::make()->exporter(AssetExporter::class),
            ]);
    }
}
