<?php

namespace App\Filament\Resources\RecruitmentSkills\Pages;

use App\Filament\Resources\RecruitmentSkills\RecruitmentSkillResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRecruitmentSkill extends EditRecord
{
    protected static string $resource = RecruitmentSkillResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
