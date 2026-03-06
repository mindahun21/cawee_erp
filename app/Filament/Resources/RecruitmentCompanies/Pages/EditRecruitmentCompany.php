<?php

namespace App\Filament\Resources\RecruitmentCompanies\Pages;

use App\Filament\Resources\RecruitmentCompanies\RecruitmentCompanyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditRecruitmentCompany extends EditRecord
{
    protected static string $resource = RecruitmentCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
