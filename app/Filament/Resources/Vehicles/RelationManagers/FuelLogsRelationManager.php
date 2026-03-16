<?php

namespace App\Filament\Resources\Vehicles\RelationManagers;

use App\Filament\Resources\Vehicles\Schemas\VehicleFuelLogForm;
use App\Filament\Resources\Vehicles\Tables\VehicleFuelLogsTable;
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
