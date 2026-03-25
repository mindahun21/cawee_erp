<?php

namespace App\Filament\Resources\VehicleManagement\Vehicles\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VehicleFuelLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Fuel Log Information')
                    ->columns(['default' => 2])
                    ->schema([
                        Select::make('vehicle_id')
                            ->relationship('vehicle', 'plate_number')
                            ->required()
                            ->preload()
                            ->searchable(),
                        DatePicker::make('date')
                            ->default(now())
                            ->required(),
                        TextInput::make('quantity')
                            ->label('Quantity (Liters)')
                            ->numeric()
                            ->required(),
                        TextInput::make('cost')
                            ->numeric()
                            ->prefix('Amt')
                            ->required(),
                        TextInput::make('odometer_reading')
                            ->numeric()
                            ->required(),
                    ]),
            ]);
    }
}
