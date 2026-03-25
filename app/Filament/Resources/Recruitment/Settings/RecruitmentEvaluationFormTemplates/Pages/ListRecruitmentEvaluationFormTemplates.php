<?php

namespace App\Filament\Resources\Recruitment\Settings\RecruitmentEvaluationFormTemplates\Pages;

use App\Filament\Resources\Recruitment\Settings\RecruitmentEvaluationFormTemplates\RecruitmentEvaluationFormTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentEvaluationFormTemplates extends ListRecords
{
    protected static string $resource = RecruitmentEvaluationFormTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
