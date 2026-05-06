<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use App\Filament\Resources\Maintenances\Tables\MaintenancesTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class MaintenancesRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenances';

    protected static ?string $title = 'Maintenance History';

    public function table(Table $table): Table
    {
        return MaintenancesTable::configure($table)
            ->headerActions([
                \Filament\Tables\Actions\CreateAction::make(),
            ]);
    }
}
