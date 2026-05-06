<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Vehicle Details')
                    ->tabs([
                        Tab::make('General Information')
                            ->columns(['default' => 2])
                            ->schema([
                                TextInput::make('plate_number')
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                Select::make('vehicle_type_id')
                                    ->relationship('type', 'name')
                                    ->required()
                                    ->preload()
                                    ->searchable()
                                    ->createOptionForm([
                                        TextInput::make('name')->required()->unique('vehicle_types', 'name'),
                                    ]),
                                TextInput::make('manufacturer'),
                                TextInput::make('model'),
                                TextInput::make('year_manufactured'),
                                TextInput::make('color'),
                                Select::make('vehicle_status_id')
                                    ->label('Current Status')
                                    ->relationship('statusRecord', 'name')
                                    ->required()
                                    ->preload()
                                    ->searchable()
                                    ->createOptionForm([
                                        TextInput::make('name')->required()->unique('vehicle_statuses', 'name'),
                                    ]),
                                Select::make('current_location_id')
                                    ->label('Current Location')
                                    ->relationship('currentLocation', 'location_name')
                                    ->preload()
                                    ->searchable()
                                    ->createOptionForm([
                                        TextInput::make('location_name')->required()->unique('locations', 'location_name'),
                                    ]),
                                Toggle::make('is_active')
                                    ->default(true)
                                    ->required(),
                            ]),
                        Tab::make('Technical Specifications')
                            ->columns(['default' => 3])
                            ->schema([
                                TextInput::make('country_manufacturer'),
                                TextInput::make('engine_size_cc')
                                    ->label('Engine Size')
                                    ->numeric()
                                    ->suffix('CC'),
                                TextInput::make('horsepower')
                                    ->numeric()
                                    ->suffix('HP'),
                                TextInput::make('number_of_cylinders')
                                    ->numeric()
                                    ->integer(),
                                Select::make('fuel_type')
                                    ->options([
                                        'Diesel' => 'Diesel',
                                        'Petrol' => 'Petrol',
                                        'Electric' => 'Electric',
                                        'Hybrid' => 'Hybrid',
                                    ])
                                    ->native(false),
                                TextInput::make('capacity'),
                                TextInput::make('chassis_number'),
                                TextInput::make('motor_number'),
                                TextInput::make('general_weight')
                                    ->numeric()
                                    ->suffix('KG'),
                                TextInput::make('single_weight')
                                    ->numeric()
                                    ->suffix('KG'),
                            ]),
                        Tab::make('Acquisition & Valuation')
                            ->columns(['default' => 2])
                            ->schema([
                                Select::make('supplier_id')
                                    ->relationship('supplier', 'name')
                                    ->preload()
                                    ->searchable()
                                    ->createOptionForm([
                                        TextInput::make('name')->required()->unique('donors', 'name'),
                                    ]),
                                Select::make('acquisition_status')
                                    ->options([
                                        'Purchased' => 'Purchased',
                                        'Leased' => 'Leased',
                                        'Donation' => 'Donation',
                                        'Loaned' => 'Loaned',
                                        'New' => 'New',
                                        'Used' => 'Used',
                                    ])
                                    ->required()
                                    ->native(false),
                                DatePicker::make('purchase_date'),
                                TextInput::make('kms_driven_at_purchase')
                                    ->numeric()
                                    ->label('Kms Driven at Purchase'),
                                TextInput::make('purchase_price')
                                    ->numeric()
                                    ->prefix('Amt'),
                                Select::make('currency')
                                    ->label('Currency')
                                    ->options(fn () => \App\Models\Currency::pluck('code', 'code')->toArray())
                                    ->searchable()
                                    ->default('ETB')
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('code')->required()->unique('currencies', 'code'),
                                        TextInput::make('name')->required(),
                                        TextInput::make('exchange_rate')->numeric()->required(),
                                    ]),
                            ]),
                        Tab::make('Insurance & Inspection')
                            ->columns(['default' => 2])
                            ->schema([
                                Section::make('Insurance Documentation')
                                    ->columns(['default' => 2])
                                    ->schema([
                                        Select::make('insurance_provider')
                                            ->label('Insurance Provider')
                                            ->options([
                                                'Nyala Insurance' => 'Nyala Insurance',
                                                'Nile Insurance' => 'Nile Insurance',
                                                'Awash Insurance' => 'Awash Insurance',
                                                'United Insurance' => 'United Insurance',
                                                'Africa Insurance' => 'Africa Insurance',
                                                'Commercial Bank of Ethiopia' => 'Commercial Bank of Ethiopia',
                                            ])
                                            ->searchable()
                                            ->native(false),
                                        TextInput::make('insurance_policy_number')
                                            ->label('Policy Number'),
                                        FileUpload::make('insurance_certificate')
                                            ->label('Upload Certificate')
                                            ->disk('public')
                                            ->directory('vehicle-insurances')
                                            ->columnSpanFull(),
                                        TextInput::make('general_insurance')
                                            ->label('General Insurance (Legacy Note)')
                                            ->placeholder('Historical data...')
                                            ->toggleable(isToggledHiddenByDefault: true),
                                        TextInput::make('third_party_insurance')
                                            ->label('Third Party Insurance (Legacy Note)')
                                            ->placeholder('Historical data...')
                                            ->toggleable(isToggledHiddenByDefault: true),
                                    ]),
                                TextInput::make('trade_license_number'),
                                Section::make('Technical Inspection')
                                    ->columns(['default' => 2])
                                    ->schema([
                                        DatePicker::make('latest_technical_inspection_date'),
                                        DatePicker::make('latest_technical_inspection_expiry'),
                                    ]),
                                Section::make('General Inspection')
                                    ->columns(['default' => 2])
                                    ->schema([
                                        DatePicker::make('latest_general_inspection_date'),
                                        DatePicker::make('latest_general_inspection_expiry'),
                                    ]),
                                Section::make('Third Party Inspection')
                                    ->columns(['default' => 2])
                                    ->schema([
                                        DatePicker::make('latest_third_party_inspection_date')
                                            ->label('Latest Date'),
                                        DatePicker::make('latest_third_party_inspection_expiry')
                                            ->label('Expiry Date'),
                                    ]),
                                DatePicker::make('insurance_renewal_date')
                                    ->label('Insurance Renewal Date'),
                            ]),
                        Tab::make('Remarks')
                            ->schema([
                                Textarea::make('remarks')
                                    ->label('Vehicle Remarks & History')
                                    ->placeholder('Enter long-form notes, historical details, or special conditions...')
                                    ->rows(10)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
