<?php

namespace App\Filament\Resources\VehicleManagement\Vehicles\RelationManagers;

use App\Filament\Resources\VehicleManagement\VehicleAssignments\Schemas\VehicleAssignmentForm;
use App\Filament\Resources\VehicleManagement\VehicleAssignments\Tables\VehicleAssignmentsTable;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class AssignmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'assignments';

    public function form(Schema $schema): Schema
    {
        return VehicleAssignmentForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return VehicleAssignmentsTable::configure($table)
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
