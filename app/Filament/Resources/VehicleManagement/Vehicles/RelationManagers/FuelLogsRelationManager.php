<?php

namespace App\Filament\Resources\VehicleManagement\Vehicles\RelationManagers;

use App\Filament\Resources\VehicleManagement\Vehicles\Schemas\VehicleFuelLogForm;
use App\Filament\Resources\VehicleManagement\Vehicles\Tables\VehicleFuelLogsTable;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class FuelLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'fuelLogs';

    public function form(Schema $schema): Schema
    {
        return VehicleFuelLogForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return VehicleFuelLogsTable::configure($table)
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
