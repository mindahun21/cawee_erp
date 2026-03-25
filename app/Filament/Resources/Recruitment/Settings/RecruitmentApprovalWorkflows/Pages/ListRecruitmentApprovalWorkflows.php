<?php

namespace App\Filament\Resources\Recruitment\Settings\RecruitmentApprovalWorkflows\Pages;

use App\Filament\Concerns\HasRecruitmentSettingsNavigation;
use App\Filament\Resources\Recruitment\Settings\RecruitmentApprovalWorkflows\RecruitmentApprovalWorkflowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecruitmentApprovalWorkflows extends ListRecords
{
    use HasRecruitmentSettingsNavigation;

    protected static string $resource = RecruitmentApprovalWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
