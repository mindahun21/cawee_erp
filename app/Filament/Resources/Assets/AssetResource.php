<?php

namespace App\Filament\Resources\Assets;

use App\Filament\Resources\Assets\Pages\CreateAsset;
use App\Filament\Resources\Assets\Pages\EditAsset;
use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Filament\Resources\Assets\Pages\ViewAsset;
use App\Filament\Resources\Assets\Schemas\AssetForm;
use App\Filament\Resources\Assets\Tables\AssetsTable;
use App\Models\Asset;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssetResource extends Resource
{
    protected static ?string $model = Asset::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventory Mgmt';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Fixed Assets';

    protected static ?string $pluralModelLabel = 'Fixed Assets';

    protected static ?string $modelLabel = 'Fixed Asset';

    protected static ?string $recordTitleAttribute = null;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('is_fixed_asset', true);
    }

    public static function form(Schema $schema): Schema
    {
        return AssetForm::configure($schema, isFixedAsset: true);
    }

    public static function table(Table $table): Table
    {
        return AssetsTable::configure($table, isFixedAsset: true);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\Assets\RelationManagers\DepreciationLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssets::route('/'),
            'create' => CreateAsset::route('/create'),
            'view' => ViewAsset::route('/{record}'),
            'edit' => EditAsset::route('/{record}/edit'),
        ];
    }
}
