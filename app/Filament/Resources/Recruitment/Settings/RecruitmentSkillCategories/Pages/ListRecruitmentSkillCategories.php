<?php

namespace App\Filament\Resources\Recruitment\Settings\RecruitmentSkillCategories\Pages;

use App\Filament\Concerns\HasRecruitmentSettingsNavigation;
use App\Filament\Resources\Recruitment\Settings\RecruitmentSkillCategories\RecruitmentSkillCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentSkillCategories extends ListRecords
{
    use HasRecruitmentSettingsNavigation;

    protected static string $resource = RecruitmentSkillCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
