<?php

namespace App\Filament\Resources\VehicleManagement\VehicleAssignments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

class VehicleAssignmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('vehicle_id')
                    ->relationship('vehicle', 'plate_number')
                    ->required()
                    ->preload()
                    ->searchable(),
                Select::make('employee_id')
                    ->label('Assigned Staff')
                    ->relationship('employee', 'first_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->first_name . ' ' . $record->last_name)
                    ->required()
                    ->preload()
                    ->searchable(),
                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->preload()
                    ->searchable(),
                DatePicker::make('assigned_date')
                    ->default(now())
                    ->required(),
                DatePicker::make('return_date'),
                Select::make('status')
                    ->options([
                        'Active' => 'Active',
                        'Completed' => 'Completed',
                        'Cancelled' => 'Cancelled',
                    ])
                    ->required()
                    ->default('Active'),
                Textarea::make('remarks')
                    ->columnSpanFull(),
            ]);
    }
}
