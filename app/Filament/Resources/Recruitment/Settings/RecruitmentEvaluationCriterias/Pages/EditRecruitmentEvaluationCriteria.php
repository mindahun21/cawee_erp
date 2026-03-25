<?php

namespace App\Filament\Resources\Recruitment\Settings\RecruitmentEvaluationCriterias\Pages;

use App\Filament\Resources\Recruitment\Settings\RecruitmentEvaluationCriterias\RecruitmentEvaluationCriteriaResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditRecruitmentEvaluationCriteria extends EditRecord
{
    protected static string $resource = RecruitmentEvaluationCriteriaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
