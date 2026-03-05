<?php

namespace App\Filament\Resources\RecruitmentIndustries\Pages;

use App\Filament\Resources\RecruitmentIndustries\RecruitmentIndustryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentIndustries extends ListRecords
{
    protected static string $resource = RecruitmentIndustryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
