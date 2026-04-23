<?php

namespace App\Filament\Resources\Recruitment\RecruitmentPlans\Pages;

use App\Filament\Resources\Recruitment\RecruitmentPlans\RecruitmentPlanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRecruitmentPlan extends CreateRecord
{
    protected static string $resource = RecruitmentPlanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
