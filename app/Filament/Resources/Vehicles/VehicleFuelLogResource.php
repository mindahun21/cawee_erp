<?php

namespace App\Filament\Resources\Vehicles;

use App\Filament\Resources\Vehicles\Pages\CreateVehicleFuelLog;
use App\Filament\Resources\Vehicles\Pages\EditVehicleFuelLog;
use App\Filament\Resources\Vehicles\Pages\ListVehicleFuelLogs;
use App\Filament\Resources\Vehicles\Schemas\VehicleFuelLogForm;
use App\Filament\Resources\Vehicles\Tables\VehicleFuelLogsTable;
use App\Models\VehicleFuelLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class VehicleFuelLogResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = VehicleFuelLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-fire';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory and Asset';

    protected static ?int $navigationSort = 120;

    protected static ?string $navigationLabel = 'Fuel Logs';

    public static function form(Schema $schema): Schema
    {
        return VehicleFuelLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VehicleFuelLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVehicleFuelLogs::route('/'),
            'create' => CreateVehicleFuelLog::route('/create'),
            'edit' => EditVehicleFuelLog::route('/{record}/edit'),
        ];
    }
}
