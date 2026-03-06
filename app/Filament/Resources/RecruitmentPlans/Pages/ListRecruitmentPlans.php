<?php

namespace App\Filament\Resources\RecruitmentPlans\Pages;

use App\Filament\Resources\RecruitmentPlans\RecruitmentPlanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentPlans extends ListRecords
{
    protected static string $resource = RecruitmentPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
