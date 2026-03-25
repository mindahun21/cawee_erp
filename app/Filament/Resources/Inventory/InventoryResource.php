<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\Pages\ListInventory;
use App\Filament\Resources\Inventory\Pages\CreateInventory;
use App\Filament\Resources\Inventory\Pages\EditInventory;
use App\Filament\Resources\Inventory\Pages\ViewInventory;
use App\Filament\Resources\Inventory\Schemas\InventoryForm;
use App\Filament\Resources\Inventory\Schemas\InventoryInfolist;
use App\Filament\Resources\Inventory\Tables\InventoryTable;
use App\Models\ItemWarehouse;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryResource extends Resource
{
    protected static ?string $model = ItemWarehouse::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory and Asset';

    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Inventory';

    protected static ?string $pluralModelLabel = 'Inventory';

    protected static ?string $modelLabel = 'Inventory Item';

    protected static ?string $recordTitleAttribute = 'sku';

    public static function form(Schema $schema): Schema
    {
        return InventoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return InventoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InventoryTable::configure($table);
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
            'index' => ListInventory::route('/'),
            'create' => CreateInventory::route('/create'),
            'view' => ViewInventory::route('/{record}'),
            'edit' => EditInventory::route('/{record}/edit'),
        ];
    }
}
