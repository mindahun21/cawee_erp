<?php

namespace App\Filament\Resources\VehicleManagement\Vehicles;

use App\Filament\Resources\VehicleManagement\Vehicles\Pages\CreateVehicle;
use App\Filament\Resources\VehicleManagement\Vehicles\Pages\EditVehicle;
use App\Filament\Resources\VehicleManagement\Vehicles\Pages\ListVehicles;
use App\Filament\Resources\VehicleManagement\Vehicles\RelationManagers\AssignmentsRelationManager;
use App\Filament\Resources\VehicleManagement\Vehicles\RelationManagers\FuelLogsRelationManager;
use App\Filament\Resources\VehicleManagement\Vehicles\RelationManagers\MaintenancesRelationManager;
use App\Filament\Resources\VehicleManagement\Vehicles\Schemas\VehicleForm;
use App\Filament\Resources\VehicleManagement\Vehicles\Tables\VehiclesTable;
use App\Models\Vehicle;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    protected static string|UnitEnum|null $navigationGroup = 'Vehicle Management';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Vehicles';

    protected static ?string $pluralModelLabel = 'Vehicles';

    protected static ?string $modelLabel = 'Vehicle';

    public static function form(Schema $schema): Schema
    {
        return VehicleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VehiclesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            AssignmentsRelationManager::class,
            MaintenancesRelationManager::class,
            FuelLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVehicles::route('/'),
            'create' => CreateVehicle::route('/create'),
            'edit' => EditVehicle::route('/{record}/edit'),
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
