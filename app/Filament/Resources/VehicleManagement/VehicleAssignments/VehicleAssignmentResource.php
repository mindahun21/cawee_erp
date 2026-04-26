<?php

namespace App\Filament\Resources\VehicleManagement\VehicleAssignments;

use App\Filament\Resources\VehicleManagement\VehicleAssignments\Pages\CreateVehicleAssignment;
use App\Filament\Resources\VehicleManagement\VehicleAssignments\Pages\EditVehicleAssignment;
use App\Filament\Resources\VehicleManagement\VehicleAssignments\Pages\ListVehicleAssignments;
use App\Filament\Resources\VehicleManagement\VehicleAssignments\Schemas\VehicleAssignmentForm;
use App\Filament\Resources\VehicleManagement\VehicleAssignments\Tables\VehicleAssignmentsTable;
use App\Models\VehicleAssignment;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Traits\BelongsToModule;

class VehicleAssignmentResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = VehicleAssignment::class;

    protected static string|UnitEnum|null $navigationGroup = 'Vehicle Management';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-identification';

    protected static ?int $navigationSort = 21;

    public static function form(Schema $schema): Schema
    {
        return VehicleAssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VehicleAssignmentsTable::configure($table);
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
            'index' => ListVehicleAssignments::route('/'),
            'create' => CreateVehicleAssignment::route('/create'),
            'edit' => EditVehicleAssignment::route('/{record}/edit'),
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
