<?php

namespace App\Filament\Clusters\Planning\Resources\PlanningKpis;

use App\Filament\Clusters\Planning;
use App\Filament\Clusters\Planning\Resources\PlanningKpis\Pages\CreatePlanningKpi;
use App\Filament\Clusters\Planning\Resources\PlanningKpis\Pages\EditPlanningKpi;
use App\Filament\Clusters\Planning\Resources\PlanningKpis\Pages\ListPlanningKpis;
use App\Filament\Clusters\Planning\Resources\PlanningKpis\Pages\ViewPlanningKpi;
use App\Filament\Clusters\Planning\Resources\PlanningKpis\Schemas\PlanningKpiForm;
use App\Filament\Clusters\Planning\Resources\PlanningKpis\Schemas\PlanningKpiInfolist;
use App\Filament\Clusters\Planning\Resources\PlanningKpis\Tables\PlanningKpisTable;
use App\Models\PlanningKpi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlanningKpiResource extends Resource
{
    protected static ?string $model = PlanningKpi::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $cluster = Planning::class;

    protected static ?string $recordTitleAttribute = 'indicator_name';

    public static function form(Schema $schema): Schema
    {
        return PlanningKpiForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PlanningKpiInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlanningKpisTable::configure($table);
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
            'index' => ListPlanningKpis::route('/'),
            'create' => CreatePlanningKpi::route('/create'),
            'view' => ViewPlanningKpi::route('/{record}'),
            'edit' => EditPlanningKpi::route('/{record}/edit'),
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
