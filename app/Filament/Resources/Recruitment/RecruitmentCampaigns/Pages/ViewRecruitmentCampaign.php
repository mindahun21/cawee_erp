<?php

namespace App\Filament\Resources\Recruitment\RecruitmentCampaigns\Pages;

use App\Filament\Resources\Recruitment\RecruitmentCampaigns\RecruitmentCampaignResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions\Action;
use App\Models\Recruitment\RecruitmentCampaign;
use App\Services\Recruitment\RecruitmentApprovalService;
use Filament\Notifications\Notification;

class ViewRecruitmentCampaign extends ViewRecord
{
    protected static string $resource = RecruitmentCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => $this->record->isEditable()),

            Action::make('submit')
                ->label('Submit for Approval')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(function () {
                    if ($this->record->status !== RecruitmentCampaign::STATUS_DRAFT) {
                        return false;
                    }
                    if (RecruitmentApprovalService::hasBeenRejected($this->record)) {
                        return RecruitmentApprovalService::wasEditedAfterRejection($this->record);
                    }
                    return true;
                })
                ->requiresConfirmation()
                ->action(function () {
                    RecruitmentApprovalService::submitForApproval($this->record);
                    Notification::make()->title('Campaign submitted for approval')->success()->send();
                }),

            Action::make('approve')
                ->label('Approve Step')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => RecruitmentApprovalService::canApprove(auth()->user(), $this->record, 'recruitment_campaign'))
                ->requiresConfirmation()
                ->action(function () {
                    RecruitmentApprovalService::approveStage($this->record, auth()->user());
                    Notification::make()->title('Stage approved')->success()->send();
                }),

            Action::make('reject')
                ->label('Reject Step')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => RecruitmentApprovalService::canApprove(auth()->user(), $this->record, 'recruitment_campaign'))
                ->requiresConfirmation()
                ->modalHeading('Reject Recruitment Campaign')
                ->form([
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Reason for Rejection')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    RecruitmentApprovalService::rejectStage($this->record, auth()->user(), $data['notes']);
                    Notification::make()->title('Campaign returned for revision')->danger()->send();
                }),

            DeleteAction::make()
                ->visible(fn () => $this->record->status === RecruitmentCampaign::STATUS_DRAFT),
        ];
    }
}
