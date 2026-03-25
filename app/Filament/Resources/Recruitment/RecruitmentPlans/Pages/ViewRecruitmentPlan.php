<?php

namespace App\Filament\Resources\Recruitment\RecruitmentPlans\Pages;

use App\Filament\Resources\Recruitment\RecruitmentPlans\RecruitmentPlanResource;
use App\Models\Recruitment\RecruitmentPlan;
use App\Services\Recruitment\RecruitmentApprovalService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;

class ViewRecruitmentPlan extends ViewRecord
{
    protected static string $resource = RecruitmentPlanResource::class;

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
                    if ($this->record->status !== RecruitmentPlan::STATUS_DRAFT) {
                        return false;
                    }
                    // If previously rejected, only allow resubmit after editing
                    if (RecruitmentApprovalService::hasBeenRejected($this->record)) {
                        return RecruitmentApprovalService::wasEditedAfterRejection($this->record);
                    }
                    return true;
                })
                ->requiresConfirmation()
                ->action(function () {
                    DB::transaction(function () {
                        $this->record->update(['status' => RecruitmentPlan::STATUS_SUBMITTED]);
                        RecruitmentApprovalService::initialise($this->record, 'recruitment_plan');
                    });
                    Notification::make()->title('Plan submitted for approval')->success()->send();
                }),

            Action::make('approve')
                ->label('Approve Step')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => RecruitmentApprovalService::canApprove(auth()->user(), $this->record, 'recruitment_plan'))
                ->requiresConfirmation()
                ->action(function () {
                    $pending = RecruitmentApprovalService::pendingRecordFor(auth()->user(), $this->record, 'recruitment_plan');
                    if ($pending) {
                        RecruitmentApprovalService::approve($this->record, 'recruitment_plan', $pending->stage_order, auth()->user());
                        Notification::make()->title('Stage approved')->success()->send();
                    }
                }),

            Action::make('reject')
                ->label('Reject Step')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => RecruitmentApprovalService::canApprove(auth()->user(), $this->record, 'recruitment_plan'))
                ->requiresConfirmation()
                ->modalHeading('Reject Recruitment Plan')
                ->form([
                    \Filament\Forms\Components\Textarea::make('notes')
                        ->label('Reason for Rejection')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    $pending = RecruitmentApprovalService::pendingRecordFor(auth()->user(), $this->record, 'recruitment_plan');
                    if ($pending) {
                        RecruitmentApprovalService::reject($this->record, 'recruitment_plan', $pending->stage_order, auth()->user(), $data['notes']);
                        Notification::make()->title('Plan returned for revision')->danger()->send();
                    }
                }),
        ];
    }
}
