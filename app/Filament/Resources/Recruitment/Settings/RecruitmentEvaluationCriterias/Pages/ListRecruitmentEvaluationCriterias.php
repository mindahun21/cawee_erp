<?php

namespace App\Filament\Resources\Recruitment\Settings\RecruitmentEvaluationCriterias\Pages;

use App\Filament\Resources\Recruitment\Settings\RecruitmentEvaluationCriterias\RecruitmentEvaluationCriteriaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentEvaluationCriterias extends ListRecords
{
    protected static string $resource = RecruitmentEvaluationCriteriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
