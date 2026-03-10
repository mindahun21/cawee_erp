<?php

namespace App\Filament\Resources\RecruitmentPlans;

use App\Filament\Resources\RecruitmentPlans\Pages\CreateRecruitmentPlan;
use App\Filament\Resources\RecruitmentPlans\Pages\EditRecruitmentPlan;
use App\Filament\Resources\RecruitmentPlans\Pages\ListRecruitmentPlans;
use App\Filament\Resources\RecruitmentPlans\Schemas\RecruitmentPlanForm;
use App\Filament\Resources\RecruitmentPlans\Tables\RecruitmentPlansTable;
use App\Models\RecruitmentPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RecruitmentPlanResource extends Resource
{
    protected static ?string $model = RecruitmentPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Recruitment';
    protected static ?string $navigationLabel = 'Plans';

    protected static ?string $recordTitleAttribute = 'plan_name';

    public static function form(Schema $schema): Schema
    {
        return RecruitmentPlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecruitmentPlansTable::configure($table);
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
            'index' => ListRecruitmentPlans::route('/'),
            'create' => CreateRecruitmentPlan::route('/create'),
            'edit' => EditRecruitmentPlan::route('/{record}/edit'),
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
