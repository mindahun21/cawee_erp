<?php

namespace App\Filament\Resources\VehicleManagement\Vehicles\RelationManagers;

use App\Filament\Resources\VehicleManagement\Vehicles\Schemas\VehicleMaintenanceForm;
use App\Filament\Resources\VehicleManagement\Vehicles\Tables\VehicleMaintenancesTable;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MaintenancesRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenances';

    public function form(Schema $schema): Schema
    {
        return VehicleMaintenanceForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return VehicleMaintenancesTable::configure($table)
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
