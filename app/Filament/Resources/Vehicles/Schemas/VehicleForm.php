<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
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
                                    ->searchable(),
                                TextInput::make('manufacturer'),
                                TextInput::make('model'),
                                TextInput::make('year_manufactured'),
                                TextInput::make('color'),
                                Select::make('vehicle_status_id')
                                    ->label('Current Status')
                                    ->relationship('statusRecord', 'name')
                                    ->required()
                                    ->preload()
                                    ->searchable(),
                                Select::make('current_location_id')
                                    ->label('Current Location')
                                    ->relationship('currentLocation', 'location_name')
                                    ->preload()
                                    ->searchable(),
                                Toggle::make('is_active')
                                    ->default(true)
                                    ->required(),
                            ]),
                        Tab::make('Technical Specifications')
                            ->columns(['default' => 3])
                            ->schema([
                                TextInput::make('country_manufacturer'),
                                TextInput::make('engine_size_cc')
                                    ->label('Engine Size (CC)'),
                                TextInput::make('horsepower'),
                                TextInput::make('number_of_cylinders')
                                    ->numeric(),
                                TextInput::make('fuel_type'),
                                TextInput::make('capacity'),
                                TextInput::make('chassis_number'),
                                TextInput::make('motor_number'),
                                TextInput::make('general_weight'),
                                TextInput::make('single_weight'),
                            ]),
                        Tab::make('Acquisition & Valuation')
                            ->columns(['default' => 2])
                            ->schema([
                                Select::make('supplier_id')
                                    ->relationship('supplier', 'name')
                                    ->preload()
                                    ->searchable(),
                                Select::make('acquisition_status')
                                    ->options([
                                        'New' => 'New',
                                        'Used' => 'Used',
                                        'Donation' => 'Donation',
                                    ]),
                                DatePicker::make('purchase_date'),
                                TextInput::make('kms_driven_at_purchase')
                                    ->numeric()
                                    ->label('Kms Driven at Purchase'),
                                TextInput::make('purchase_price')
                                    ->numeric()
                                    ->prefix('Amt'),
                                TextInput::make('currency')
                                    ->default('ETB'),
                            ]),
                        Tab::make('Insurance & Inspection')
                            ->columns(['default' => 2])
                            ->schema([
                                TextInput::make('general_insurance'),
                                TextInput::make('third_party_insurance'),
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
                                DatePicker::make('latest_third_party_inspection_date'),
                                DatePicker::make('insurance_renewal_date'),
                            ]),
                        Tab::make('Remarks')
                            ->schema([
                                Textarea::make('remarks')
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
