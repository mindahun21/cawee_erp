<?php

namespace App\Filament\Resources\Recruitment\RecruitmentPlans\Pages;

use App\Filament\Resources\Recruitment\RecruitmentPlans\RecruitmentPlanResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditRecruitmentPlan extends EditRecord
{
    protected static string $resource = RecruitmentPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
