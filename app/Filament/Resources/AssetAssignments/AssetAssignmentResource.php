<?php

namespace App\Filament\Resources\AssetAssignments;

use App\Filament\Resources\AssetAssignments\Pages\CreateAssetAssignment;
use App\Filament\Resources\AssetAssignments\Pages\EditAssetAssignment;
use App\Filament\Resources\AssetAssignments\Pages\ListAssetAssignments;
use App\Filament\Resources\AssetAssignments\Pages\ViewAssetAssignment;
use App\Filament\Resources\AssetAssignments\Schemas\AssetAssignmentForm;
use App\Filament\Resources\AssetAssignments\Tables\AssetAssignmentsTable;
use App\Models\AssetAssignment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssetAssignmentResource extends Resource
{
    protected static ?string $model = AssetAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-plus';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory Mgmt';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'id';

    public static function form(Schema $schema): Schema
    {
        return AssetAssignmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetAssignmentsTable::configure($table);
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
            'index' => ListAssetAssignments::route('/'),
            'create' => CreateAssetAssignment::route('/create'),
            'view' => ViewAssetAssignment::route('/{record}'),
            'edit' => EditAssetAssignment::route('/{record}/edit'),
        ];
    }
}
