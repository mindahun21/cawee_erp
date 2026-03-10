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

class AssetsTable
{
    public static function configure(Table $table, bool $isFixedAsset = true, string $resource = \App\Filament\Resources\Assets\AssetResource::class): Table
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
                            <span class="text-gray-200">|</span>
                            <button type="button" 
                                x-on:click="$wire.mountTableAction(\'delete\', '.$record->id.')"
                                class="hover-action-link text-danger-600 hover:text-danger-700 font-medium">Delete</button>
                        </div>
                    '), position: 'below'),
                \Filament\Tables\Columns\TextColumn::make('assetCategory.name')
                    ->label('Category')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->sortable()
                    ->toggleable(),
                \Filament\Tables\Columns\TextColumn::make('barcode')
                    ->searchable()
                    ->visible($isFixedAsset),
                \Filament\Tables\Columns\TextColumn::make('qr_code')
                    ->label('QR Code')
                    ->searchable()
                    ->visible($isFixedAsset),
                \Filament\Tables\Columns\TextColumn::make('rfid_tag')
                    ->label('RFID Tag')
                    ->searchable()
                    ->visible($isFixedAsset),
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
                    ->sortable()
                    ->visible($isFixedAsset),
                \Filament\Tables\Columns\TextColumn::make('condition.name')
                    ->label('Condition')
                    ->sortable()
                    ->visible($isFixedAsset),
                \Filament\Tables\Columns\TextColumn::make('quantity')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record): string => $record->is_low_stock ? 'danger' : 'success')
                    ->visible(!$isFixedAsset),
                \Filament\Tables\Columns\TextColumn::make('location.location_name')
                    ->label('Location')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('purchase_cost')
                    ->money('INR')
                    ->sortable(),
                \Filament\Tables\Columns\TextColumn::make('monthly_depreciation')
                    ->label('Monthly Depr.')
                    ->money('INR')
                    ->sortable()
                    ->visible($isFixedAsset),
                \Filament\Tables\Columns\TextColumn::make('current_value')
                    ->label('Current Val.')
                    ->money('INR')
                    ->sortable()
                    ->visible($isFixedAsset),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('asset_category_id')
                    ->label('Category')
                    ->relationship('assetCategory', 'name'),
                \Filament\Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('asset_status_id')
                    ->label('Status')
                    ->relationship('statusRecord', 'name'),
                \Filament\Tables\Filters\SelectFilter::make('asset_condition_id')
                    ->label('Condition')
                    ->relationship('condition', 'name'),
                \Filament\Tables\Filters\SelectFilter::make('acquisition_type_id')
                    ->label('Acquisition Type')
                    ->relationship('acquisitionTypeRecord', 'name'),
                \Filament\Tables\Filters\TernaryFilter::make('is_low_stock')
                    ->label('Stock Level')
                    ->placeholder('All Items')
                    ->trueLabel('Low Stock Only')
                    ->falseLabel('Sufficient Stock')
                    ->queries(
                        true: fn ($query) => $query->whereColumn('quantity', '<=', 'min_stock_level'),
                        false: fn ($query) => $query->whereColumn('quantity', '>', 'min_stock_level'),
                    ),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
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
