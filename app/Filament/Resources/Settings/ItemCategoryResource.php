<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Clusters\Settings;
use App\Filament\Resources\Settings\ItemCategoryResource\Pages\ManageItemCategories;
use App\Filament\Resources\Settings\ItemCategoryResource\Schemas\ItemCategoryForm;
use App\Filament\Resources\Settings\ItemCategoryResource\Tables\ItemCategoriesTable;
use App\Models\ItemCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ItemCategoryResource extends Resource
{
    protected static ?string $model = ItemCategory::class;

    protected static string|null $cluster = Settings::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationLabel = 'Item Categories';

    protected static ?int $navigationSort = 14;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ItemCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ItemCategoriesTable::configure($table);
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
            'index' => ManageItemCategories::route('/'),
        ];
    }
}
