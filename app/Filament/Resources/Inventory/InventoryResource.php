<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\Pages\ListInventory;
use App\Filament\Resources\Inventory\Pages\CreateInventory;
use App\Filament\Resources\Inventory\Pages\EditInventory;
use App\Filament\Resources\Inventory\Pages\ViewInventory;
use App\Filament\Resources\Assets\Schemas\AssetForm;
use App\Filament\Resources\Assets\Tables\AssetsTable;
use App\Models\Asset;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory Mgmt';

    protected static ?string $navigationLabel = 'Inventory / Consumables';

    protected static ?string $pluralModelLabel = 'Inventory / Consumables';

    protected static ?string $modelLabel = 'Inventory Item';

    protected static ?string $recordTitleAttribute = null;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_fixed_asset', false);
    }

    public static function form(Schema $schema): Schema
    {
        return AssetForm::configure($schema, isFixedAsset: false);
    }

    public static function table(Table $table): Table
    {
        return AssetsTable::configure($table, isFixedAsset: false, resource: static::class);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\Assets\RelationManagers\StocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventory::route('/'),
            'create' => CreateInventory::route('/create'),
            'view' => ViewInventory::route('/{record}'),
            'edit' => EditInventory::route('/{record}/edit'),
        ];
    }
}
