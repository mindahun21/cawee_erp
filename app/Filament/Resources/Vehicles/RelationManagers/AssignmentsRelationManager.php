<?php

namespace App\Filament\Resources\Vehicles\RelationManagers;

use App\Filament\Resources\VehicleAssignments\Schemas\VehicleAssignmentForm;
use App\Filament\Resources\VehicleAssignments\Tables\VehicleAssignmentsTable;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
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
