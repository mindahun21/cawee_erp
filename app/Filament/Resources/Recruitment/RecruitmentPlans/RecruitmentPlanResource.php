<?php

namespace App\Filament\Resources\Recruitment\RecruitmentPlans;

use App\Filament\Resources\Recruitment\RecruitmentPlans\Pages\CreateRecruitmentPlan;
use App\Filament\Resources\Recruitment\RecruitmentPlans\Pages\EditRecruitmentPlan;
use App\Filament\Resources\Recruitment\RecruitmentPlans\Pages\ListRecruitmentPlans;
use App\Filament\Resources\Recruitment\RecruitmentPlans\Schemas\RecruitmentPlanForm;
use App\Filament\Resources\Recruitment\RecruitmentPlans\Tables\RecruitmentPlansTable;
use App\Models\Recruitment\RecruitmentPlan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RecruitmentPlanResource extends Resource
{
    protected static ?string $model = RecruitmentPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Recruitment';

    protected static ?int $navigationSort = 2;

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
