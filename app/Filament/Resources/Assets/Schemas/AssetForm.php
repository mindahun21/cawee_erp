<?php

namespace App\Filament\Resources\Assets\Schemas;

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
    public static function configure(Schema $schema, bool $isFixedAsset = true): Schema
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
                        Select::make('asset_category_id')
                            ->label('Category')
                            ->relationship('assetCategory', 'name')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->reactive(),
                        TextInput::make('model')
                            ->maxLength(255),
                        TextInput::make('serial_number')
                            ->label('Serial Number')
                            ->maxLength(255)
                            ->required($isFixedAsset)
                            ->visible($isFixedAsset),
                        TextInput::make('barcode')
                            ->label('Barcode')
                            ->default(fn () => 'BC-' . strtoupper(bin2hex(random_bytes(4))))
                            ->disabled()
                            ->dehydrated()
                            ->unique(ignoreRecord: true)
                            ->visible($isFixedAsset),
                        TextInput::make('qr_code')
                            ->label('QR Code')
                            ->default(fn () => 'QR-' . strtoupper(bin2hex(random_bytes(4))))
                            ->disabled()
                            ->dehydrated()
                            ->unique(ignoreRecord: true)
                            ->visible($isFixedAsset),
                        TextInput::make('rfid_tag')
                            ->label('RFID Tag')
                            ->default(fn () => 'RFID-' . strtoupper(bin2hex(random_bytes(4))))
                            ->disabled()
                            ->dehydrated()
                            ->unique(ignoreRecord: true)
                            ->visible($isFixedAsset),
                        Textarea::make('description')
                            ->columnSpanFull(),
                    ]),

                Section::make($isFixedAsset ? 'Classification & Location' : 'Classification')
                    ->columns(2)
                    ->schema([
                        Select::make('location_id')
                            ->relationship('location', 'location_name')
                            ->preload()
                            ->searchable()
                            ->visible($isFixedAsset),
                        Select::make('department_id')
                            ->relationship('department', 'name')
                            ->preload()
                            ->searchable()
                            ->visible($isFixedAsset),
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
                            ->default(fn () => \App\Models\AssetStatus::where('name', 'Available')->first()?->id),
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
                            ->relationship('donor', 'id') // Assuming ID is what we want to use, or name if available
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->first_name . ' ' . $record->last_name)
                            ->searchable()
                            ->preload()
                            ->visible(fn (Get $get) => $get('acquisition_type') === 'Donation'),
                        TextInput::make('purchase_cost')
                            ->numeric()
                            ->prefix('Amount')
                            ->default(0)
                            ->hidden(fn (Get $get) => $get('acquisition_type') === 'Donation'),
                        DatePicker::make('purchase_date')
                            ->hidden(fn (Get $get) => $get('acquisition_type') === 'Donation'),
                        DatePicker::make('warranty_expiry_date')
                            ->label('Warranty Expiry')
                            ->visible($isFixedAsset)
                            ->hidden(fn (Get $get) => $get('acquisition_type') === 'Donation'),
                        Select::make('supplier_id')
                            ->label('Supplier')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload()
                            ->hidden(fn (Get $get) => $get('acquisition_type') === 'Donation'),
                        Select::make('depreciation_id')
                            ->label('Depreciation Type')
                            ->relationship('depreciation', 'name')
                            ->preload()
                            ->searchable()
                            ->visible($isFixedAsset)
                            ->hidden(fn (Get $get) => $get('acquisition_type') === 'Donation'),
                        Textarea::make('contract_details')
                            ->label('Contract / Lease Details')
                            ->columnSpanFull()
                            ->visible($isFixedAsset),
                    ]),

                Section::make('Stock Allocation')
                    ->description('Allocate stock across multiple locations')
                    ->visible(!$isFixedAsset)
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
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $stocks = $get('../../stocks') ?? [];
                                        $total = collect($stocks)->sum('quantity');
                                        $set('../../quantity', $total);
                                    }),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Add Location Stock')
                            ->columnSpanFull()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $stocks = $get('stocks') ?? [];
                                $total = collect($stocks)->sum('quantity');
                                $set('quantity', $total);
                            }),
                    ]),

                Section::make('Inventory Settings')
                    ->visible(!$isFixedAsset)
                    ->columns(2)
                    ->schema([
                        TextInput::make('quantity')
                            ->label('Total Quantity')
                            ->numeric()
                            ->default(1)
                            ->required()
                            ->readOnly()
                            ->helperText('Automatically calculated from stock allocation'),
                        TextInput::make('min_stock_level')
                            ->numeric()
                            ->default(0)
                            ->required(),
                    ]),
                
                Toggle::make('is_fixed_asset')
                    ->default($isFixedAsset)
                    ->dehydrated()
                    ->hidden(),

                Section::make('Vehicle / Machinery Details')
                    ->relationship('vehicleDetail')
                    ->columns(3)
                    ->visible(fn (Get $get) => $isFixedAsset && in_array(\App\Models\AssetCategory::find($get('asset_category_id'))?->name, ['Vehicles', 'Machinery']))
                    ->schema([
                        TextInput::make('plate_number')
                            ->unique(ignoreRecord: true),
                        TextInput::make('chassis_number'),
                        TextInput::make('motor_number'),
                        TextInput::make('engine_size'),
                        TextInput::make('fuel_type'),
                        TextInput::make('capacity'),
                        TextInput::make('color'),
                        TextInput::make('horsepower'),
                        TextInput::make('year_manufactured'),
                        TextInput::make('manufacturer'),
                        TextInput::make('insurance_company'),
                        TextInput::make('insurance_policy_no'),
                        DatePicker::make('insurance_expiration_date'),
                        DatePicker::make('technical_inspection_date'),
                        DatePicker::make('technical_inspection_expiration_date'),
                    ]),
            ]);
    }
}
