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

class AssetsTable
{
    public static function configure(Table $table, string $resource = \App\Filament\Resources\Assets\AssetResource::class): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => new \Illuminate\Support\HtmlString('
                        <div class="hover-actions-wrapper flex gap-2 pt-1 items-center">
                            <a href="'.$resource::getUrl('view', ['record' => $record]).'" class="hover-action-link text-gray-400 hover:text-gray-500">View</a>
                            <span class="text-gray-200">|</span>
                            <a href="'.$resource::getUrl('edit', ['record' => $record]).'" class="hover-action-link text-primary-600 hover:text-primary-700">Edit</a>
                        </div>
                    '), position: 'below'),
                \Filament\Tables\Columns\TextColumn::make('assetModel.category.name')
                    ->label('Category')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('assetModel.type.name')
                    ->label('Type of Asset')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('assetModel.model_number')
                    ->label('Model No')
                    ->sortable()
                    ->toggleable(),
                \Filament\Tables\Columns\TextColumn::make('assetModel.manufacturer.name')
                    ->label('Manufacturer')
                    ->sortable()
                    ->toggleable(),
                \Filament\Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->sortable()
                    ->toggleable(),
                \Filament\Tables\Columns\TextColumn::make('barcode')
                    ->searchable()
                    ->toggleable(),
                \Filament\Tables\Columns\TextColumn::make('qr_code')
                    ->label('QR Code')
                    ->searchable()
                    ->toggleable(),
                \Filament\Tables\Columns\TextColumn::make('rfid_tag')
                    ->label('RFID Tag')
                    ->searchable()
                    ->toggleable(),
                \Filament\Tables\Columns\TextColumn::make('statusRecord.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'Available' => 'success',
                        'Assigned' => 'info',
                        'Maintenance' => 'warning',
                        'Disposed' => 'danger',
                        'Lost' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('condition.name')
                    ->label('Condition')
                    ->sortable()
                    ->toggleable(),
                \Filament\Tables\Columns\TextColumn::make('unit.name')
                    ->label('Unit')
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
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->relationship('assetModel.category', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('manufacturer')
                    ->label('Manufacturer')
                    ->relationship('assetModel.manufacturer', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('assetType')
                    ->label('Type of Asset')
                    ->relationship('assetModel.type', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('asset_model_id')
                    ->label('Model')
                    ->relationship('assetModel', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('asset_status_id')
                    ->label('Status')
                    ->relationship('statusRecord', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('asset_condition_id')
                    ->label('Condition')
                    ->relationship('condition', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('acquisition_type_id')
                    ->label('Acquisition Type')
                    ->relationship('acquisitionTypeRecord', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->recordActions([
                DeleteAction::make(),
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
