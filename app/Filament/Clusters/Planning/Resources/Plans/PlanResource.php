<?php

namespace App\Filament\Clusters\Planning\Resources\Plans;

use App\Filament\Clusters\Planning;
use App\Filament\Clusters\Planning\Resources\Plans\Pages\CreatePlan;
use App\Filament\Clusters\Planning\Resources\Plans\Pages\EditPlan;
use App\Filament\Clusters\Planning\Resources\Plans\Pages\ListPlans;
use App\Filament\Clusters\Planning\Resources\Plans\Pages\ViewPlan;
use App\Filament\Clusters\Planning\Resources\Plans\Schemas\PlanForm;
use App\Filament\Clusters\Planning\Resources\Plans\Schemas\PlanInfolist;
use App\Filament\Clusters\Planning\Resources\Plans\Tables\PlansTable;
use App\Models\Plan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $cluster = Planning::class;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return PlanForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PlanInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TasksRelationManager::class,
            RelationManagers\KpisRelationManager::class,
            RelationManagers\ResourceAllocationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlans::route('/'),
            'create' => CreatePlan::route('/create'),
            'view' => ViewPlan::route('/{record}'),
            'edit' => EditPlan::route('/{record}/edit'),
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
