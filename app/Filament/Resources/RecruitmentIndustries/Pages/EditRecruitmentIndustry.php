<?php

namespace App\Filament\Resources\RecruitmentIndustries\Pages;

use App\Filament\Resources\RecruitmentIndustries\RecruitmentIndustryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRecruitmentIndustry extends EditRecord
{
    protected static string $resource = RecruitmentIndustryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
