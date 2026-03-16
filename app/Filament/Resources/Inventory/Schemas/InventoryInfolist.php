<?php

namespace App\Filament\Resources\Inventory\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InventoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Inventory Record Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('sku')
                                    ->label('SKU')
                                    ->weight('bold'),
                                TextEntry::make('quantity')
                                    ->label('Current Quantity'),
                                TextEntry::make('min_stock_value')
                                    ->label('Minimum Stock Level'),
                            ]),
                    ]),

                Section::make('Item & Warehouse Details')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('item.name')
                                    ->label('Item Name')
                                    ->hint(fn ($record) => $record->item?->code ?? '')
                                    ->weight('bold'),
                                TextEntry::make('warehouse.name')
                                    ->label('Warehouse Location')
                                    ->weight('bold'),
                            ]),
                    ]),

                Section::make('Acquisition & Costing')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('acquisitionType.name')
                                    ->label('Acquisition Type'),
                                TextEntry::make('purchase_cost')
                                    ->money()
                                    ->label('Purchase Cost'),
                                TextEntry::make('purchase_date')
                                    ->date()
                                    ->label('Purchase Date'),
                                TextEntry::make('warranty_expiry')
                                    ->date()
                                    ->label('Warranty Expiry'),
                                TextEntry::make('supplier.name')
                                    ->label('Supplier'),
                                TextEntry::make('donor.full_name')
                                    ->label('Donor'),
                                TextEntry::make('currency.name')
                                    ->label('Currency'),
                            ]),
                    ]),
            ]);
    }
}
