<?php

namespace App\Filament\Resources\RecruitmentIndustries;

use App\Filament\Resources\RecruitmentIndustries\Pages\CreateRecruitmentIndustry;
use App\Filament\Resources\RecruitmentIndustries\Pages\EditRecruitmentIndustry;
use App\Filament\Resources\RecruitmentIndustries\Pages\ListRecruitmentIndustries;
use App\Filament\Resources\RecruitmentIndustries\Schemas\RecruitmentIndustryForm;
use App\Filament\Resources\RecruitmentIndustries\Tables\RecruitmentIndustriesTable;
use App\Models\RecruitmentIndustry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RecruitmentIndustryResource extends Resource
{
    protected static ?string $model = RecruitmentIndustry::class;

    protected static string|UnitEnum|null $navigationGroup = 'Recruitment';

    protected static ?string $navigationParentItem = 'Settings';

    protected static ?string $navigationLabel = 'Industry List';

    protected static ?int $navigationSort = 7;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return RecruitmentIndustryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecruitmentIndustriesTable::configure($table);
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
            'index' => ListRecruitmentIndustries::route('/'),
            'create' => CreateRecruitmentIndustry::route('/create'),
            'edit' => EditRecruitmentIndustry::route('/{record}/edit'),
        ];
    }
}
