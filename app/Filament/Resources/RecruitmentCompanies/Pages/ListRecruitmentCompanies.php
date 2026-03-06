<?php

namespace App\Filament\Resources\RecruitmentCompanies\Pages;

use App\Filament\Resources\RecruitmentCompanies\RecruitmentCompanyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentCompanies extends ListRecords
{
    protected static string $resource = RecruitmentCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
