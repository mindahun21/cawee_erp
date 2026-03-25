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
use Illuminate\Support\Facades\DB;

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
                    DB::transaction(function () {
                        $this->record->update(['status' => RecruitmentCampaign::STATUS_SUBMITTED]);
                        RecruitmentApprovalService::initialise($this->record, 'recruitment_campaign');
                    });
                    Notification::make()->title('Campaign submitted for approval')->success()->send();
                }),

            Action::make('approve')
                ->label('Approve Step')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(function () {
                    /** @var \App\Models\User $user */
                    $user = auth()->user();
                    return RecruitmentApprovalService::canApprove($user, $this->record, 'recruitment_campaign');
                })
                ->requiresConfirmation()
                ->action(function () {
                    /** @var \App\Models\User $user */
                    $user = auth()->user();
                    $pending = RecruitmentApprovalService::pendingRecordFor($user, $this->record, 'recruitment_campaign');
                    if ($pending) {
                        RecruitmentApprovalService::approve($this->record, 'recruitment_campaign', $pending->stage_order, $user);
                        Notification::make()->title('Stage approved')->success()->send();
                    }
                }),

            Action::make('reject')
                ->label('Reject Step')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(function () {
                    /** @var \App\Models\User $user */
                    $user = auth()->user();
                    return RecruitmentApprovalService::canApprove($user, $this->record, 'recruitment_campaign');
                })
                ->requiresConfirmation()
                ->modalHeading('Reject Recruitment Campaign')
                ->form([
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Reason for Rejection')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    /** @var \App\Models\User $user */
                    $user = auth()->user();
                    $pending = RecruitmentApprovalService::pendingRecordFor($user, $this->record, 'recruitment_campaign');
                    if ($pending) {
                        RecruitmentApprovalService::reject($this->record, 'recruitment_campaign', $pending->stage_order, $user, $data['notes']);
                        Notification::make()->title('Campaign returned for revision')->danger()->send();
                    }
                }),

            DeleteAction::make()
                ->visible(fn () => $this->record->status === RecruitmentCampaign::STATUS_DRAFT),
        ];
    }
}
