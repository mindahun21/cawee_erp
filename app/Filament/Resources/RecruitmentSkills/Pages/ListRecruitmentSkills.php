<?php

namespace App\Filament\Resources\RecruitmentSkills\Pages;

use App\Filament\Resources\RecruitmentSkills\RecruitmentSkillResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentSkills extends ListRecords
{
    protected static string $resource = RecruitmentSkillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
