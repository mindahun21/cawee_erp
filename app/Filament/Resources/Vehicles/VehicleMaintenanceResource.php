<?php

namespace App\Filament\Resources\Vehicles;

use App\Filament\Resources\Vehicles\Pages\CreateVehicleMaintenance;
use App\Filament\Resources\Vehicles\Pages\EditVehicleMaintenance;
use App\Filament\Resources\Vehicles\Pages\ListVehicleMaintenances;
use App\Filament\Resources\Vehicles\Schemas\VehicleMaintenanceForm;
use App\Filament\Resources\Vehicles\Tables\VehicleMaintenancesTable;
use App\Models\VehicleMaintenance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehicleMaintenanceResource extends Resource
{
    protected static ?string $model = VehicleMaintenance::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string|\UnitEnum|null $navigationGroup = 'Vehicles';

    protected static ?int $navigationSort = 130;

    protected static ?string $navigationLabel = 'Vehicle Maintenances';

    public static function form(Schema $schema): Schema
    {
        return VehicleMaintenanceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VehicleMaintenancesTable::configure($table);
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
            'index' => ListVehicleMaintenances::route('/'),
            'create' => CreateVehicleMaintenance::route('/create'),
            'edit' => EditVehicleMaintenance::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
