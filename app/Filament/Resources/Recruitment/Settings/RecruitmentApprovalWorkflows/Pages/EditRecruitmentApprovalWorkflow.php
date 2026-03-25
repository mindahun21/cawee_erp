<?php

namespace App\Filament\Resources\Recruitment\Settings\RecruitmentApprovalWorkflows\Pages;

use App\Filament\Resources\Recruitment\Settings\RecruitmentApprovalWorkflows\RecruitmentApprovalWorkflowResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRecruitmentApprovalWorkflow extends EditRecord
{
    protected static string $resource = RecruitmentApprovalWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
