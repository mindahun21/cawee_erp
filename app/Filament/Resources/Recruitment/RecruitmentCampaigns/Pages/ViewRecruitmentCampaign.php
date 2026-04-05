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

            Action::make('create_schedule')
                ->label('Create Schedule')
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->visible(fn () => $this->record->applications()
                    ->whereIn('status', [
                        \App\Models\Recruitment\RecruitmentApplication::STATUS_UNDER_REVIEW,
                        \App\Models\Recruitment\RecruitmentApplication::STATUS_SHORTLISTED,
                    ])->exists())
                ->url(fn () => \App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\RecruitmentInterviewScheduleResource::getUrl('create', [
                    'campaign_id' => $this->record->id,
                ])),

            Action::make('submit')
                ->label('Submit for Approval')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(function () {
                    if ($this->record->status !== RecruitmentCampaign::STATUS_DRAFT) {
                        return false;
                    }
                    if ($this->record->end_date && $this->record->end_date < today()) {
                        return false;
                    }
                    if ($this->record->recruitment_plan_id) {
                        $plan = $this->record->recruitmentPlan;
                        if (!$plan || $plan->status === \App\Models\Recruitment\RecruitmentPlan::STATUS_CLOSED || ($plan->end_date && $plan->end_date < today())) {
                            return false;
                        }
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
                ->visible(fn () => ($user = auth()->user()) instanceof \App\Models\User && RecruitmentApprovalService::canApprove($user, $this->record, 'recruitment_campaign'))
                ->requiresConfirmation()
                ->action(function () {
                    /** @var \App\Models\User $user */
                    $user = auth()->user();
                    RecruitmentApprovalService::approveStage($this->record, $user);
                    Notification::make()->title('Stage approved')->success()->send();
                }),

            Action::make('reject')
                ->label('Reject Step')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => ($user = auth()->user()) instanceof \App\Models\User && RecruitmentApprovalService::canApprove($user, $this->record, 'recruitment_campaign'))
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
                    RecruitmentApprovalService::rejectStage($this->record, $user, $data['notes']);
                    Notification::make()->title('Campaign returned for revision')->danger()->send();
                }),

            Action::make('pause')
                ->label('Pause Campaign')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->visible(fn () => $this->record->status === RecruitmentCampaign::STATUS_ACTIVE)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => RecruitmentCampaign::STATUS_PAUSED]);
                    Notification::make()->title('Campaign paused')->warning()->send();
                }),

            Action::make('resume')
                ->label('Resume Campaign')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->visible(fn () => $this->record->status === RecruitmentCampaign::STATUS_PAUSED && (!$this->record->end_date || $this->record->end_date >= today()))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => RecruitmentCampaign::STATUS_ACTIVE]);
                    Notification::make()->title('Campaign resumed')->success()->send();
                }),

            Action::make('close')
                ->label('Close Campaign')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => in_array($this->record->status, [RecruitmentCampaign::STATUS_ACTIVE, RecruitmentCampaign::STATUS_PAUSED]))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => RecruitmentCampaign::STATUS_CLOSED]);
                    Notification::make()->title('Campaign closed')->danger()->send();
                }),

            Action::make('re_open')
                ->label('Re-open Campaign')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->visible(fn () => $this->record->status === RecruitmentCampaign::STATUS_CLOSED && (!$this->record->end_date || $this->record->end_date->isFuture()))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => RecruitmentCampaign::STATUS_ACTIVE]);
                    Notification::make()->title('Campaign re-opened')->success()->send();
                }),

            DeleteAction::make()
                ->visible(fn () => $this->record->status === RecruitmentCampaign::STATUS_DRAFT),
        ];
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Details';
    }

    public function getContentTabIcon(): ?string
    {
        return 'heroicon-o-information-circle';
    }
}
