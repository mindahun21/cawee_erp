<?php

namespace App\Filament\Resources\Inventory\Schemas;

use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class InventoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        Select::make('item_id')
                            ->relationship('item', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabledOn('edit'),
                        Select::make('warehouse_id')
                            ->relationship('warehouse', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabledOn('edit'),
                        TextInput::make('sku')
                            ->label('SKU Number')
                            ->prefix(fn () => \App\Models\PrefixSetting::getPrefix('inventory_sku'))
                            ->default(fn () => \App\Models\PrefixSetting::where('key', 'inventory_sku')->value('next_number'))
                            ->dehydrateStateUsing(fn ($state) => \App\Models\PrefixSetting::getPrefix('inventory_sku') . $state)
                            ->unique(\App\Models\ItemWarehouse::class, 'sku', ignoreRecord: true)
                            ->live(onBlur: true)
                            ->hint(fn ($state, $record) => $state ? (\App\Models\ItemWarehouse::where('sku', \App\Models\PrefixSetting::getPrefix('inventory_sku') . $state)->when($record, fn ($q) => $q->where('id', '!=', $record->id))->exists() ? 'Already taken' : 'Available') : null)
                            ->hintColor(fn ($state, $record) => $state ? (\App\Models\ItemWarehouse::where('sku', \App\Models\PrefixSetting::getPrefix('inventory_sku') . $state)->when($record, fn ($q) => $q->where('id', '!=', $record->id))->exists() ? 'danger' : 'success') : 'gray')
                            ->required(),
                        TextInput::make('batch_number')
                            ->label('Batch / Lot Number'),
                        TextInput::make('bin_location')
                            ->label('Bin / Shelf Location')
                            ->placeholder('e.g. Rack A-12'),
                        DatePicker::make('expiry_date')
                            ->label('Expiry Date'),
                        Select::make('acquisition_type_id')
                            ->relationship('acquisitionType', 'name')
                            ->searchable()
                            ->preload()
                            ->disabledOn('edit'),
                        Select::make('currency_id')
                            ->relationship('currency', 'name')
                            ->searchable()
                            ->preload()
                            ->disabledOn('edit'),
                        TextInput::make('purchase_cost')
                            ->numeric()
                            ->prefix('$')
                            ->disabledOn('edit'),
                        DatePicker::make('purchase_date')
                            ->disabledOn('edit'),
                        DatePicker::make('warranty_expiry')
                            ->disabledOn('edit'),
                        Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('donor_id')
                            ->relationship('donor', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                            ->searchable()
                            ->preload()
                            ->disabledOn('edit'),
                        TextInput::make('quantity')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->disabledOn('edit'),
                        TextInput::make('min_stock_value')
                            ->numeric()
                            ->default(0)
                            ->label('Min Stock Value'),
                    ]),
            ]);
    }
}
