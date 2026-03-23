<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\Settings\AssetCategoryResource\Pages\CreateAssetCategory;
use App\Filament\Resources\Settings\AssetCategoryResource\Pages\EditAssetCategory;
use App\Filament\Resources\Settings\AssetCategoryResource\Pages\ListAssetCategories;
use App\Filament\Resources\Settings\AssetCategoryResource\Pages\ViewAssetCategory;
use App\Filament\Resources\Settings\AssetCategoryResource\Schemas\AssetCategoryForm;
use App\Filament\Resources\Settings\AssetCategoryResource\Tables\AssetCategoriesTable;
use App\Models\AssetCategory;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssetCategoryResource extends Resource
{
    protected static ?string $model = AssetCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $cluster = \App\Filament\Clusters\Settings::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AssetCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetCategoriesTable::configure($table);
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
            'index' => ListAssetCategories::route('/'),
            'create' => CreateAssetCategory::route('/create'),
            'view' => ViewAssetCategory::route('/{record}'),
            'edit' => EditAssetCategory::route('/{record}/edit'),
        ];
    }
}
