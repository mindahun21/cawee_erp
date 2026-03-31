<?php

namespace App\Filament\Resources\Recruitment\RecruitmentOffers\Pages;

use App\Filament\Resources\Recruitment\RecruitmentOffers\RecruitmentOfferResource;
use App\Models\Recruitment\RecruitmentOffer;
use App\Services\Recruitment\RecruitmentApprovalService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewRecruitmentOffer extends ViewRecord
{
    protected static string $resource = RecruitmentOfferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => $this->record->status === RecruitmentOffer::STATUS_DRAFT),

            Action::make('submit')
                ->label('Submit for Approval')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(function () {
                    if ($this->record->status !== RecruitmentOffer::STATUS_DRAFT) {
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
                    Notification::make()->title('Offer submitted for approval')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            Action::make('approve')
                ->label('Approve Step')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => ($user = auth()->user()) instanceof \App\Models\User
                    && RecruitmentApprovalService::canApprove($user, $this->record, 'recruitment_offer'))
                ->requiresConfirmation()
                ->action(function () {
                    /** @var \App\Models\User $user */
                    $user = auth()->user();
                    RecruitmentApprovalService::approveStage($this->record, $user);
                    Notification::make()->title('Stage approved')->success()->send();
                    $this->refreshFormData(['status']);
                }),

            Action::make('reject_stage')
                ->label('Reject Step')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => ($user = auth()->user()) instanceof \App\Models\User
                    && RecruitmentApprovalService::canApprove($user, $this->record, 'recruitment_offer'))
                ->requiresConfirmation()
                ->modalHeading('Return Offer for Revision')
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
                    Notification::make()->title('Offer returned for revision')->warning()->send();
                    $this->refreshFormData(['status']);
                }),
        ];
    }
}
