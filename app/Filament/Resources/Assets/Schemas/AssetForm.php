<?php

namespace App\Filament\Resources\Assets\Schemas;

use App\Models\Asset;
use App\Models\AssetStatus;
use App\Models\AcquisitionType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make()
                    ->tabs([
                        // ─────────────────────────────────────────────────
                        // TAB 1 – General & Identification
                        // ─────────────────────────────────────────────────
                        Tab::make('General Info')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Section::make()
                                    ->columns(['default' => 2])
                                    ->schema([
                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('asset_tag')
                                            ->label('Asset Tag #')
                                            ->prefix(fn () => \App\Models\PrefixSetting::getPrefix('asset_tag'))
                                            ->default(fn () => \App\Models\PrefixSetting::where('key', 'asset_tag')->value('next_number'))
                                            ->dehydrateStateUsing(fn ($state) => $state ? \App\Models\PrefixSetting::getPrefix('asset_tag') . $state : null)
                                            ->unique(Asset::class, 'asset_tag', ignoreRecord: true)
                                            ->live(onBlur: true)
                                            ->helperText('Printed on physical sticker attached to the asset.'),

                                        Select::make('asset_model_id')
                                            ->label('Model')
                                            ->relationship('assetModel', 'name')
                                            ->required()
                                            ->preload()
                                            ->searchable()
                                            ->live()
                                            ->afterStateUpdated(function ($state, $component) {
                                                if (!$state) return;
                                                $model = \App\Models\AssetModel::with(['type', 'category'])->find($state);
                                                if ($model) {
                                                    $isVehicle = str_contains(strtolower($model->type?->name ?? ''), 'vehicle') || 
                                                                str_contains(strtolower($model->category?->name ?? ''), 'vehicle');
                                                    if ($isVehicle) {
                                                         \Filament\Notifications\Notification::make()
                                                            ->title('Vehicle detected')
                                                            ->body('Redirecting you to the Vehicle Management creation page...')
                                                            ->info()
                                                            ->send();
                                                         
                                                         return redirect()->to(\App\Filament\Resources\VehicleManagement\Vehicles\VehicleResource::getUrl('create'));
                                                    }
                                                }
                                            })
                                            ->createOptionForm([
                                                TextInput::make('name')
                                                    ->label('Model Name')
                                                    ->required()
                                                    ->maxLength(255),
                                                Select::make('asset_type_id')
                                                    ->label('Asset Type')
                                                    ->relationship('type', 'name')
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state) {
                                                        if (!$state) return;
                                                        $type = \App\Models\AssetType::find($state);
                                                        if ($type && str_contains(strtolower($type->name ?? ''), 'vehicle')) {
                                                            \Filament\Notifications\Notification::make()
                                                                ->title('Vehicle detected')
                                                                ->body('Redirecting to the Vehicle Management creation page...')
                                                                ->info()
                                                                ->send();
                                                            
                                                            return redirect()->to(\App\Filament\Resources\VehicleManagement\Vehicles\VehicleResource::getUrl('create'));
                                                        }
                                                    })
                                                    ->createOptionForm([
                                                        TextInput::make('name')->required(),
                                                    ]),
                                                Select::make('asset_manufacturer_id')
                                                    ->label('Manufacturer')
                                                    ->relationship('manufacturer', 'name')
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->createOptionForm([
                                                        TextInput::make('name')->required(),
                                                    ]),
                                                Select::make('asset_category_id')
                                                    ->label('Category')
                                                    ->relationship('category', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->createOptionForm([
                                                        TextInput::make('name')->required(),
                                                    ]),
                                                TextInput::make('model_number')
                                                    ->label('Model NO.'),
                                            ]),

                                        Select::make('unit_id')
                                            ->relationship('unit', 'name')
                                            ->required()
                                            ->preload()
                                            ->searchable()
                                            ->createOptionForm([
                                                TextInput::make('name')->required(),
                                                TextInput::make('code')->required(),
                                            ]),

                                        Toggle::make('is_fixed_asset')
                                            ->label('Is Fixed Asset?')
                                            ->helperText('Fixed assets appear in depreciation and assignment tracking.')
                                            ->default(true)
                                            ->columnSpanFull(),

                                        Select::make('asset_condition_id')
                                            ->label('Condition')
                                            ->relationship('conditionRecord', 'name')
                                            ->preload()
                                            ->searchable()
                                            ->createOptionForm([
                                                TextInput::make('name')->required(),
                                            ]),

                                        Select::make('asset_status_id')
                                            ->label('Status')
                                            ->relationship('statusRecord', 'name')
                                            ->required()
                                            ->preload()
                                            ->searchable()
                                            ->default(fn () => AssetStatus::where('name', 'Available')->first()?->id)
                                            ->createOptionForm([
                                                TextInput::make('name')->required(),
                                                Textarea::make('description'),
                                            ]),

                                        Textarea::make('description')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ─────────────────────────────────────────────────
                        // TAB 2 – Tracking Codes
                        // ─────────────────────────────────────────────────
                        Tab::make('Tracking Codes')
                            ->icon('heroicon-o-qr-code')
                            ->schema([
                                Section::make()
                                    ->columns(2)
                                    ->schema([
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
                                    ]),
                            ]),

                        // ─────────────────────────────────────────────────
                        // TAB 3 – Financials & Acquisition
                        // ─────────────────────────────────────────────────
                        Tab::make('Acquisition & Cost')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Section::make()
                                    ->columns(['default' => 2])
                                    ->schema([
                                        Select::make('acquisition_type_id')
                                            ->label('Acquisition Type')
                                            ->relationship('acquisitionTypeRecord', 'name')
                                            ->live()
                                            ->preload()
                                            ->searchable()
                                            ->columnSpanFull()
                                            ->createOptionForm([
                                                TextInput::make('name')->required(),
                                                Textarea::make('description'),
                                            ]),

                                        Select::make('currency_id')
                                            ->relationship('currency', 'code')
                                            ->searchable()
                                            ->preload()
                                            ->hidden(fn (Get $get) => $get('acquisition_type') === 'Donation'),

                                        Select::make('donor_id')
                                            ->label('Donor')
                                            ->relationship('donor', 'id')
                                            ->getOptionLabelFromRecordUsing(fn ($record) => ($record->first_name ?? '') . ' ' . ($record->last_name ?? ''))
                                            ->searchable()
                                            ->preload()
                                            ->visible(fn (Get $get) => AcquisitionType::find($get('acquisition_type_id'))?->name === 'Donation')
                                            ->createOptionForm([
                                                TextInput::make('first_name')->required(),
                                                TextInput::make('last_name')->required(),
                                                TextInput::make('email')->email(),
                                            ]),

                                        TextInput::make('purchase_cost')
                                            ->numeric()
                                            ->prefix('Amount')
                                            ->default(0)
                                            ->hidden(fn (Get $get) => $get('acquisition_type') === 'Donation'),

                                        DatePicker::make('purchase_date')
                                            ->hidden(fn (Get $get) => AcquisitionType::find($get('acquisition_type_id'))?->name === 'Donation'),

                                        DatePicker::make('warranty_expiry_date')
                                            ->label('Warranty Expiry')
                                            ->afterOrEqual('purchase_date')
                                            ->validationMessages([
                                                'after_or_equal' => 'The warranty expiry date must be on or after the purchase date.',
                                            ])
                                            ->hidden(fn (Get $get) => $get('acquisition_type') === 'Donation'),

                                        Select::make('supplier_id')
                                            ->label('Supplier')
                                            ->relationship('supplier', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->hidden(fn (Get $get) => $get('acquisition_type') === 'Donation')
                                            ->createOptionForm([
                                                TextInput::make('name')->required(),
                                                TextInput::make('email')->email(),
                                                TextInput::make('phone'),
                                            ]),

                                        DatePicker::make('end_of_life_date')
                                            ->label('End of Life / Write-off Date')
                                            ->afterOrEqual('warranty_expiry_date')
                                            ->validationMessages([
                                                'after_or_equal' => 'The end of life date must be on or after the warranty expiry date.',
                                            ]),

                                        Textarea::make('notes')
                                            ->label('Notes')
                                            ->columnSpanFull(),

                                        Textarea::make('contract_details')
                                            ->label('Contract / Lease Details')
                                            ->columnSpanFull(),

                                        FileUpload::make('image')
                                            ->label('Asset Photo')
                                            ->image()
                                            ->directory('assets')
                                            ->maxSize(10240)
                                            ->helperText('Max size: 10MB (Note: PHP limit is currently 2MB).')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ─────────────────────────────────────────────────
                        // TAB 4 – Logistics & Protection
                        // ─────────────────────────────────────────────────
                        Tab::make('Logistics & Protection')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Section::make('Insurance Details')
                                    ->columns(['default' => 2])
                                    ->schema([
                                        TextInput::make('insurance_policy_no')
                                            ->label('Insurance Policy No.'),
                                        TextInput::make('insurance_provider')
                                            ->label('Insurance Provider'),
                                        DatePicker::make('insurance_expiry_date')
                                            ->label('Insurance Expiry'),
                                    ]),

                                Section::make('Location Stock Allocation')
                                    ->description('Allocate stock across multiple locations')
                                    ->hiddenOn('edit') // Fixed: hiddenOn takes string
                                    ->schema([
                                        Repeater::make('stocks')
                                            ->relationship('stocks')
                                            ->schema([
                                                Select::make('location_id')
                                                    ->relationship('location', 'location_name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->createOptionForm([
                                                        TextInput::make('location_name')
                                                            ->required()
                                                            ->maxLength(255),
                                                        TextInput::make('address')
                                                            ->maxLength(255),
                                                        TextInput::make('type')
                                                            ->maxLength(255),
                                                    ])
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
                                                    ->live()
                                                    ->createOptionForm([
                                                        TextInput::make('name')
                                                            ->required()
                                                            ->maxLength(255),
                                                        TextInput::make('code')
                                                            ->maxLength(255),
                                                        Textarea::make('description'),
                                                    ]),
                                                TextInput::make('quantity')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->required()
                                                    ->live()
                                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                                        $stocks = $get('../../') ?? [];
                                                        $total = collect($stocks)->sum(fn ($item) => (float) ($item['quantity'] ?? 0));
                                                        $set('../../../quantity', $total);
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
                                            ->columns(['default' => 3])
                                            ->defaultItems(1)
                                            ->addActionLabel('Add Location Stock')
                                            ->columnSpanFull()
                                            ->afterStateUpdated(function (Set $set, $state) {
                                                $total = collect($state)->sum(fn ($item) => (float) ($item['quantity'] ?? 0));
                                                $set('../quantity', $total);
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

                                Section::make('Disposal & Retirement')
                                    ->description('Optional: record if this asset is being retired')
                                    ->collapsed()
                                    ->columns(['default' => 2])
                                    ->schema([
                                        Select::make('disposal_method_id')
                                            ->label('Disposal Method')
                                            ->relationship('disposalMethod', 'name')
                                            ->preload()
                                            ->searchable()
                                            ->createOptionForm([
                                                TextInput::make('name')->required()->unique('asset_disposal_methods', 'name'),
                                            ]),

                                        DatePicker::make('disposal_date')
                                            ->label('Disposal Date'),

                                        TextInput::make('disposal_value')
                                            ->label('Salvage / Disposal Value')
                                            ->numeric()
                                            ->prefix('Amount'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
