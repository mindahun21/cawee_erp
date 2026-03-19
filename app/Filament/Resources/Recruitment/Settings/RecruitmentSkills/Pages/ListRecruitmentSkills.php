<?php

namespace App\Filament\Resources\Recruitment\Settings\RecruitmentSkills\Pages;

use App\Filament\Concerns\HasRecruitmentSettingsNavigation;
use App\Filament\Resources\Recruitment\Settings\RecruitmentSkills\RecruitmentSkillResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentSkills extends ListRecords
{
    use HasRecruitmentSettingsNavigation;

    protected static string $resource = RecruitmentSkillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
