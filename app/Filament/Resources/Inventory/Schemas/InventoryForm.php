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
                            ->disabledOn('edit')
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('item_code')
                                    ->default(fn () => \App\Models\PrefixSetting::where('key', 'item_code')->value('next_number'))
                                    ->required(),
                                Select::make('unit_id')
                                    ->relationship('unit', 'name')
                                    ->required()
                                    ->preload()
                                    ->searchable()
                                    ->createOptionForm([
                                        TextInput::make('name')->required(),
                                        TextInput::make('code')->required(),
                                    ]),
                            ]),
                        Select::make('warehouse_id')
                            ->relationship('warehouse', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabledOn('edit')
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('warehouse_code')
                                    ->default(fn () => \App\Models\Warehouse::generateUniqueCode())
                                    ->required(),
                                TextInput::make('address')
                                    ->maxLength(255),
                            ]),
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
                            ->disabledOn('edit')
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                            ]),
                        Select::make('currency_id')
                            ->relationship('currency', 'code')
                            ->searchable()
                            ->preload()
                            ->disabledOn('edit')
                            ->createOptionForm([
                                TextInput::make('code')->required()->maxLength(3),
                                TextInput::make('name')->required(),
                                TextInput::make('symbol')->required(),
                                TextInput::make('exchange_rate')->numeric()->required(),
                            ]),
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
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                                TextInput::make('email')->email(),
                            ]),
                        Select::make('donor_id')
                            ->relationship('donor', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                            ->searchable()
                            ->preload()
                            ->disabledOn('edit')
                            ->createOptionForm([
                                TextInput::make('first_name')->required(),
                                TextInput::make('last_name')->required(),
                            ]),
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
