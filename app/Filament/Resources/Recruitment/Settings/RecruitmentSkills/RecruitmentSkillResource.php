<?php

namespace App\Filament\Resources\Recruitment\Settings\RecruitmentSkills;

use App\Filament\Resources\Recruitment\Settings\RecruitmentSkills\Pages\CreateRecruitmentSkill;
use App\Filament\Resources\Recruitment\Settings\RecruitmentSkills\Pages\EditRecruitmentSkill;
use App\Filament\Resources\Recruitment\Settings\RecruitmentSkills\Pages\ListRecruitmentSkills;
use App\Filament\Resources\Recruitment\Settings\RecruitmentSkills\Schemas\RecruitmentSkillForm;
use App\Filament\Resources\Recruitment\Settings\RecruitmentSkills\Tables\RecruitmentSkillsTable;
use App\Models\Recruitment\RecruitmentSkill;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use App\Traits\BelongsToModule;

class RecruitmentSkillResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = RecruitmentSkill::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Skills';

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
        ];
    }
}
