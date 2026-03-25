<?php

namespace App\Filament\Resources\Recruitment\Settings\RecruitmentEvaluationCriterias\Pages;

use App\Filament\Resources\Recruitment\Settings\RecruitmentEvaluationCriterias\RecruitmentEvaluationCriteriaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRecruitmentEvaluationCriteria extends CreateRecord
{
    protected static string $resource = RecruitmentEvaluationCriteriaResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
