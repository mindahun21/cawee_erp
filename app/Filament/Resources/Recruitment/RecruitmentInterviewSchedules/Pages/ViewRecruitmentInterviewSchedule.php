<?php

namespace App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\Pages;

use App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\RecruitmentInterviewScheduleResource;
use App\Models\Recruitment\RecruitmentInterviewSchedule;
use App\Services\Recruitment\RecruitmentApprovalService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewRecruitmentInterviewSchedule extends ViewRecord
{
    protected static string $resource = RecruitmentInterviewScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => in_array($this->record->status, [RecruitmentInterviewSchedule::STATUS_DRAFT, RecruitmentInterviewSchedule::STATUS_REJECTED])),

            Action::make('submit')
                ->label('Submit for Approval')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(function () {
                    if (!in_array($this->record->status, [RecruitmentInterviewSchedule::STATUS_DRAFT, RecruitmentInterviewSchedule::STATUS_REJECTED])) {
                        return false;
                    }
                    if (\App\Services\Recruitment\RecruitmentApprovalService::hasBeenRejected($this->record)) {
                        return \App\Services\Recruitment\RecruitmentApprovalService::wasEditedAfterRejection($this->record);
                    }
                    return true;
                })
                ->requiresConfirmation()
                ->action(function () {
                    RecruitmentApprovalService::submitForApproval($this->record);
                    Notification::make()->title('Schedule submitted for approval')->success()->send();
                }),

            Action::make('approve')
                ->label('Approve Step')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(function () {
                    $user = auth()->user();
                    return $user instanceof \App\Models\User && RecruitmentApprovalService::canApprove($user, $this->record, 'recruitment_interview_schedule');
                })
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
                ->visible(function () {
                    $user = auth()->user();
                    return $user instanceof \App\Models\User && RecruitmentApprovalService::canApprove($user, $this->record, 'recruitment_interview_schedule');
                })
                ->requiresConfirmation()
                ->modalHeading('Reject Interview Schedule')
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
                    Notification::make()->title('Schedule returned for revision')->danger()->send();
                }),
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
