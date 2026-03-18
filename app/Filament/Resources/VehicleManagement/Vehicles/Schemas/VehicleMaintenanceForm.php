<?php

namespace App\Filament\Resources\VehicleManagement\Vehicles\Schemas;

use App\Models\HrSettingOption;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class VehicleMaintenanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Maintenance Information')
                    ->columns(['default' => 2])
                    ->schema([
                        Select::make('vehicle_id')
                            ->relationship('vehicle', 'plate_number')
                            ->required()
                            ->preload()
                            ->searchable(),
                        Select::make('service_type')
                            ->options(HrSettingOption::optionsFor('vehicle_service_type'))
                            ->required()
                            ->searchable(),
                        DatePicker::make('service_date')
                            ->default(now())
                            ->required(),
                        TextInput::make('cost')
                            ->numeric()
                            ->prefix('Amt')
                            ->required(),
                        DatePicker::make('next_service_date'),
                        Textarea::make('description')
                            ->columnSpanFull(),
                        Textarea::make('remarks')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
