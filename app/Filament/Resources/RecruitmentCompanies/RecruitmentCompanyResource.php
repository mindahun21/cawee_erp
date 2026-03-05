<?php

namespace App\Filament\Resources\RecruitmentCompanies;

use App\Filament\Resources\RecruitmentCompanies\Pages\CreateRecruitmentCompany;
use App\Filament\Resources\RecruitmentCompanies\Pages\EditRecruitmentCompany;
use App\Filament\Resources\RecruitmentCompanies\Pages\ListRecruitmentCompanies;
use App\Filament\Resources\RecruitmentCompanies\Schemas\RecruitmentCompanyForm;
use App\Filament\Resources\RecruitmentCompanies\Tables\RecruitmentCompaniesTable;
use App\Models\RecruitmentCompany;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RecruitmentCompanyResource extends Resource
{
    protected static ?string $model = RecruitmentCompany::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Recruitment';

    protected static ?string $navigationParentItem = 'Settings';

    protected static ?string $navigationLabel = 'Company List';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return RecruitmentCompanyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecruitmentCompaniesTable::configure($table);
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
            'index' => ListRecruitmentCompanies::route('/'),
            'create' => CreateRecruitmentCompany::route('/create'),
            'edit' => EditRecruitmentCompany::route('/{record}/edit'),
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
