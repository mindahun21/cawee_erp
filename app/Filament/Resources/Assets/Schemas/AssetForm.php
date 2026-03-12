<?php

namespace App\Filament\Resources\Assets\Schemas;

use App\Models\Asset;
use App\Models\AssetModel;
use App\Models\AssetStatus;
use App\Models\AcquisitionType;
use App\Models\AssetCategory;
use App\Models\Department;
use App\Models\Location;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('General Information')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                        TextInput::make('quantity')
                            ->label('Total Quantity')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                if ($state > 1) {
                                    $set('serial_number', null);
                                    $set('barcode', null);
                                    $set('qr_code', null);
                                    $set('rfid_tag', null);
                                } elseif ($state == 1) {
                                    $set('serial_number', \App\Models\PrefixSetting::where('key', 'asset_serial_number')->value('next_number'));
                                    $set('barcode', \App\Models\PrefixSetting::where('key', 'asset_barcode')->value('next_number'));
                                    $set('qr_code', \App\Models\PrefixSetting::where('key', 'asset_qr_code')->value('next_number'));
                                    $set('rfid_tag', \App\Models\PrefixSetting::where('key', 'asset_rfid_tag')->value('next_number'));
                                }
                            }),
                        Select::make('asset_model_id')
                            ->label('Model')
                            ->relationship('assetModel', 'name')
                            ->required()
                            ->preload()
                            ->searchable(),
                        Select::make('unit_id')
                            ->relationship('unit', 'name')
                            ->required()
                            ->preload()
                            ->searchable(),
                        TextInput::make('serial_number')
                            ->label('Serial Number')
                            ->prefix(fn () => \App\Models\PrefixSetting::getPrefix('asset_serial_number'))
                            ->default(fn () => \App\Models\PrefixSetting::where('key', 'asset_serial_number')->value('next_number'))
                            ->dehydrateStateUsing(fn ($state) => $state ? \App\Models\PrefixSetting::getPrefix('asset_serial_number') . $state : null)
                            ->unique(Asset::class, 'serial_number', ignoreRecord: true)
                            ->live(onBlur: true)
                            ->visible(fn (Get $get) => $get('quantity') <= 1)
                            ->hint(fn ($state, $record) => $state ? (Asset::where('serial_number', \App\Models\PrefixSetting::getPrefix('asset_serial_number') . $state)->when($record, fn ($q) => $q->where('id', '!=', $record->id))->exists() ? 'Already taken' : 'Available') : null)
                            ->hintColor(fn ($state, $record) => $state ? (Asset::where('serial_number', \App\Models\PrefixSetting::getPrefix('asset_serial_number') . $state)->when($record, fn ($q) => $q->where('id', '!=', $record->id))->exists() ? 'danger' : 'success') : 'gray'),
                        TextInput::make('barcode')
                            ->label('Barcode')
                            ->prefix(fn () => \App\Models\PrefixSetting::getPrefix('asset_barcode'))
                            ->default(fn () => \App\Models\PrefixSetting::where('key', 'asset_barcode')->value('next_number'))
                            ->dehydrateStateUsing(fn ($state) => $state ? \App\Models\PrefixSetting::getPrefix('asset_barcode') . $state : null)
                            ->dehydrated()
                            ->unique(Asset::class, 'barcode', ignoreRecord: true)
                            ->live(onBlur: true)
                            ->visible(fn (Get $get) => $get('quantity') <= 1)
                            ->hint(fn ($state, $record) => $state ? (Asset::where('barcode', \App\Models\PrefixSetting::getPrefix('asset_barcode') . $state)->when($record, fn ($q) => $q->where('id', '!=', $record->id))->exists() ? 'Already taken' : 'Available') : null)
                            ->hintColor(fn ($state, $record) => $state ? (Asset::where('barcode', \App\Models\PrefixSetting::getPrefix('asset_barcode') . $state)->when($record, fn ($q) => $q->where('id', '!=', $record->id))->exists() ? 'danger' : 'success') : 'gray'),
                        TextInput::make('qr_code')
                            ->label('QR Code')
                            ->prefix(fn () => \App\Models\PrefixSetting::getPrefix('asset_qr_code'))
                            ->default(fn () => \App\Models\PrefixSetting::where('key', 'asset_qr_code')->value('next_number'))
                            ->dehydrateStateUsing(fn ($state) => $state ? \App\Models\PrefixSetting::getPrefix('asset_qr_code') . $state : null)
                            ->dehydrated()
                            ->unique(Asset::class, 'qr_code', ignoreRecord: true)
                            ->live(onBlur: true)
                            ->visible(fn (Get $get) => $get('quantity') <= 1)
                            ->hint(fn ($state, $record) => $state ? (Asset::where('qr_code', \App\Models\PrefixSetting::getPrefix('asset_qr_code') . $state)->when($record, fn ($q) => $q->where('id', '!=', $record->id))->exists() ? 'Already taken' : 'Available') : null)
                            ->hintColor(fn ($state, $record) => $state ? (Asset::where('qr_code', \App\Models\PrefixSetting::getPrefix('asset_qr_code') . $state)->when($record, fn ($q) => $q->where('id', '!=', $record->id))->exists() ? 'danger' : 'success') : 'gray'),
                        TextInput::make('rfid_tag')
                            ->label('RFID Tag')
                            ->prefix(fn () => \App\Models\PrefixSetting::getPrefix('asset_rfid_tag'))
                            ->default(fn () => \App\Models\PrefixSetting::where('key', 'asset_rfid_tag')->value('next_number'))
                            ->dehydrateStateUsing(fn ($state) => $state ? \App\Models\PrefixSetting::getPrefix('asset_rfid_tag') . $state : null)
                            ->dehydrated()
                            ->unique(Asset::class, 'rfid_tag', ignoreRecord: true)
                            ->live(onBlur: true)
                            ->visible(fn (Get $get) => $get('quantity') <= 1)
                            ->hint(fn ($state, $record) => $state ? (Asset::where('rfid_tag', \App\Models\PrefixSetting::getPrefix('asset_rfid_tag') . $state)->when($record, fn ($q) => $q->where('id', '!=', $record->id))->exists() ? 'Already taken' : 'Available') : null)
                            ->hintColor(fn ($state, $record) => $state ? (Asset::where('rfid_tag', \App\Models\PrefixSetting::getPrefix('asset_rfid_tag') . $state)->when($record, fn ($q) => $q->where('id', '!=', $record->id))->exists() ? 'danger' : 'success') : 'gray'),
                        Textarea::make('description')
                            ->columnSpanFull(),
                    ]),

                Section::make('Classification & Location')
                    ->columns(2)
                    ->schema([
                        Select::make('asset_condition_id')
                            ->label('Condition')
                            ->relationship('condition', 'name')
                            ->preload()
                            ->searchable(),
                        Select::make('asset_status_id')
                            ->label('Status')
                            ->relationship('statusRecord', 'name')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->default(fn () => AssetStatus::where('name', 'Available')->first()?->id),
                    ]),

                Section::make('Acquisition & Valuation')
                    ->columns(2)
                    ->schema([
                        Select::make('acquisition_type_id')
                            ->label('Acquisition Type')
                            ->relationship('acquisitionTypeRecord', 'name')
                            ->live()
                            ->required()
                            ->preload()
                            ->searchable(),
                        Select::make('currency_id')
                            ->relationship('currency', 'code')
                            ->searchable()
                            ->preload()
                            ->hidden(fn (Get $get) => $get('acquisition_type') === 'Donation'),
                        Select::make('donor_id')
                            ->label('Donor')
                            ->relationship('donor', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->first_name . ' ' . $record->last_name)
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => AcquisitionType::find($get('acquisition_type_id'))?->name === 'Donation'),
                        TextInput::make('purchase_cost')
                            ->numeric()
                            ->prefix('Amount')
                            ->default(0)
                            ->hidden(fn (Get $get) => $get('acquisition_type') === 'Donation'),
                        DatePicker::make('purchase_date')
                            ->hidden(fn (Get $get) => AcquisitionType::find($get('acquisition_type_id'))?->name === 'Donation'),
                        DatePicker::make('warranty_expiry_date')
                            ->label('Warranty Expiry')
                            ->hidden(fn (Get $get) => $get('acquisition_type') === 'Donation'),
                        Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->hidden(fn (Get $get) => $get('acquisition_type') === 'Donation'),
                        Textarea::make('notes')
                            ->label('Notes')
                            ->columnSpanFull(),
                        Textarea::make('contract_details')
                            ->label('Contract / Lease Details')
                            ->columnSpanFull(),
                    ]),

                Section::make('Stock Allocation')
                    ->description('Allocate stock across multiple locations')
                    ->hiddenOn(['edit', 'view'])
                    ->schema([
                        \Filament\Forms\Components\Repeater::make('stocks')
                            ->relationship('stocks')
                            ->schema([
                                Select::make('location_id')
                                    ->relationship('location', 'location_name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->rules([
                                        fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                                            $stocks = $get('../../stocks') ?? [];
                                            $combos = collect($stocks)
                                                ->map(fn ($item) => ($item['location_id'] ?? '') . '-' . ($item['department_id'] ?? ''))
                                                ->filter(fn ($combo) => $combo !== '-');
                                            
                                            $duplicates = $combos->duplicates();
                                            if ($duplicates->isNotEmpty()) {
                                                $fail('Each location and department combination must be unique.');
                                            }
                                        },
                                    ]),
                                Select::make('department_id')
                                    ->relationship('department', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->live(),
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        // Use relative path to repeater items
                                        $stocks = $get('../../') ?? [];
                                        $total = collect($stocks)->sum(fn ($item) => (float) ($item['quantity'] ?? 0));
                                        $set('../../../quantity', $total);
                                        
                                        // Sync unique fields
                                        if ($total > 1) {
                                            $set('../../../serial_number', null);
                                            $set('../../../barcode', null);
                                            $set('../../../qr_code', null);
                                            $set('../../../rfid_tag', null);
                                        } elseif ($total == 1) {
                                            $set('../../../serial_number', \App\Models\PrefixSetting::where('key', 'asset_serial_number')->value('next_number'));
                                            $set('../../../barcode', \App\Models\PrefixSetting::where('key', 'asset_barcode')->value('next_number'));
                                            $set('../../../qr_code', \App\Models\PrefixSetting::where('key', 'asset_qr_code')->value('next_number'));
                                            $set('../../../rfid_tag', \App\Models\PrefixSetting::where('key', 'asset_rfid_tag')->value('next_number'));
                                        }
                                    }),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Add Location Stock')
                            ->columnSpanFull()
                            ->afterStateUpdated(function (Set $set, $state) {
                                $total = collect($state)->sum(fn ($item) => (float) ($item['quantity'] ?? 0));
                                $set('../quantity', $total);

                                // Sync unique fields
                                if ($total > 1) {
                                    $set('../serial_number', null);
                                    $set('../barcode', null);
                                    $set('../qr_code', null);
                                    $set('../rfid_tag', null);
                                } elseif ($total == 1) {
                                    $set('../serial_number', \App\Models\PrefixSetting::where('key', 'asset_serial_number')->value('next_number'));
                                    $set('../barcode', \App\Models\PrefixSetting::where('key', 'asset_barcode')->value('next_number'));
                                    $set('../qr_code', \App\Models\PrefixSetting::where('key', 'asset_qr_code')->value('next_number'));
                                    $set('../rfid_tag', \App\Models\PrefixSetting::where('key', 'asset_rfid_tag')->value('next_number'));
                                }
                            }),
                    ]),


            ]);
    }
}
