<?php

namespace App\Filament\Resources\VehicleManagement\Vehicles;

use App\Filament\Resources\VehicleManagement\Vehicles\Pages\CreateVehicleFuelLog;
use App\Filament\Resources\VehicleManagement\Vehicles\Pages\EditVehicleFuelLog;
use App\Filament\Resources\VehicleManagement\Vehicles\Pages\ListVehicleFuelLogs;
use App\Filament\Resources\VehicleManagement\Vehicles\Schemas\VehicleFuelLogForm;
use App\Filament\Resources\VehicleManagement\Vehicles\Tables\VehicleFuelLogsTable;
use App\Models\VehicleFuelLog;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class VehicleFuelLogResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = VehicleFuelLog::class;

    protected static string|UnitEnum|null $navigationGroup = 'Vehicle Management';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-fire';

    protected static ?int $navigationSort = 23;

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
