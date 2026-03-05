<?php

namespace App\Filament\Resources\RecruitmentSkills;

use App\Filament\Resources\RecruitmentSkills\Pages\CreateRecruitmentSkill;
use App\Filament\Resources\RecruitmentSkills\Pages\EditRecruitmentSkill;
use App\Filament\Resources\RecruitmentSkills\Pages\ListRecruitmentSkills;
use App\Filament\Resources\RecruitmentSkills\Schemas\RecruitmentSkillForm;
use App\Filament\Resources\RecruitmentSkills\Tables\RecruitmentSkillsTable;
use App\Models\RecruitmentSkill;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RecruitmentSkillResource extends Resource
{
    protected static ?string $model = RecruitmentSkill::class;

    protected static string|UnitEnum|null $navigationGroup = 'Recruitment';

    protected static ?string $navigationParentItem = 'Settings';

    protected static ?string $navigationLabel = 'Skills';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return RecruitmentSkillForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RecruitmentSkillsTable::configure($table);
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
            'index' => ListRecruitmentSkills::route('/'),
            'create' => CreateRecruitmentSkill::route('/create'),
            'edit' => EditRecruitmentSkill::route('/{record}/edit'),
        ];
    }
}
