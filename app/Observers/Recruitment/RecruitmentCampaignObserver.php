<?php

namespace App\Observers\Recruitment;

use App\Filament\Resources\Recruitment\RecruitmentCampaigns\RecruitmentCampaignResource;
use App\Mail\Recruitment\RecruitmentCampaignApprovedMail;
use App\Mail\Recruitment\RecruitmentCampaignRejectedMail;
use App\Mail\Recruitment\RecruitmentCampaignSubmittedMail;
use App\Models\Recruitment\RecruitmentCampaign;
use App\Models\User;
use App\Services\Recruitment\RecruitmentApprovalService;
use Filament\Actions\Action as NotificationAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RecruitmentCampaignObserver
{
    /**
     * Handle the RecruitmentCampaign "updated" event.
     */
    public function updated(RecruitmentCampaign $campaign): void
    {
        if (! $campaign->wasChanged('status')) {
            return;
        }

        try {
            $this->handleStatusChange(
                $campaign,
                $campaign->getOriginal('status'),
                $campaign->status
            );
        } catch (\Throwable $e) {
            Log::error('RecruitmentCampaignObserver: failed to handle status change', [
                'campaign_id' => $campaign->id,
                'old_status'  => $campaign->getOriginal('status'),
                'new_status'  => $campaign->status,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    private function handleStatusChange(RecruitmentCampaign $campaign, string $from, string $to): void
    {
        if ($to === RecruitmentCampaign::STATUS_REJECTED) {
            $this->notifyCampaignRejected($campaign);
            return;
        }

        match ($to) {
            RecruitmentCampaign::STATUS_SUBMITTED => $this->notifyCampaignSubmitted($campaign),
            RecruitmentCampaign::STATUS_ACTIVE    => $this->notifyCampaignApproved($campaign),
            default                               => null,
        };
    }

    // ── Campaign submitted → notify ONLY the first stage approver role ──

    private function notifyCampaignSubmitted(RecruitmentCampaign $campaign): void
    {
        $campaign->loadMissing(['jobPosition', 'creator']);
        $viewUrl = $this->getCampaignUrl($campaign);

        $nextPending = RecruitmentApprovalService::nextPendingRecord($campaign, 'recruitment_campaign');

        if (! $nextPending) {
            return;
        }

        $approvers = User::role($nextPending->required_role)->get();

        foreach ($approvers as $approver) {
            Mail::to($approver->email)->queue(new RecruitmentCampaignSubmittedMail($campaign, $viewUrl));

            FilamentNotification::make()
                ->title('Recruitment Campaign Awaiting Approval')
                ->body("Campaign \"{$campaign->title}\" for {$campaign->jobPosition->title} submitted by {$campaign->creator->name}.")
                ->icon('heroicon-o-megaphone')
                ->iconColor('warning')
                ->actions([
                    NotificationAction::make('review')
                        ->label('Review Campaign')
                        ->url($viewUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($approver);
        }
    }

    // ── Campaign fully approved (active) → notify the creator + followers ──

    private function notifyCampaignApproved(RecruitmentCampaign $campaign): void
    {
        $campaign->loadMissing(['jobPosition', 'creator']);
        $viewUrl = $this->getCampaignUrl($campaign);

        $creator = $campaign->creator;
        if ($creator) {
            Mail::to($creator->email)->queue(new RecruitmentCampaignApprovedMail($campaign, $viewUrl));

            FilamentNotification::make()
                ->title('Recruitment Campaign Approved & Active')
                ->body("Your campaign \"{$campaign->title}\" for {$campaign->jobPosition->title} has been approved and is now active.")
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->actions([
                    NotificationAction::make('view')
                        ->label('View Campaign')
                        ->url($viewUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($creator);
        }

        $followers = $campaign->followers;
        if ($followers->isNotEmpty()) {
            FilamentNotification::make()
                ->title("Campaign Activated: {$campaign->title}")
                ->body('The campaign you are following is now active.')
                ->icon('heroicon-o-megaphone')
                ->iconColor('info')
                ->sendToDatabase($followers);
        }
    }

    // ── Campaign rejected → notify the creator only ─────────────────────

    private function notifyCampaignRejected(RecruitmentCampaign $campaign): void
    {
        $campaign->loadMissing(['jobPosition', 'creator']);
        $creator = $campaign->creator;

        if (! $creator) {
            return;
        }

        $viewUrl = $this->getCampaignUrl($campaign);

        $rejectionNotes = RecruitmentApprovalService::previousRejectionNotes($campaign)
            ?? 'No reason provided.';

        Mail::to($creator->email)->queue(new RecruitmentCampaignRejectedMail($campaign, $viewUrl));

        FilamentNotification::make()
            ->title('Recruitment Campaign Returned for Revision')
            ->body("Your campaign \"{$campaign->title}\" was returned. Reason: {$rejectionNotes}")
            ->icon('heroicon-o-x-circle')
            ->iconColor('danger')
            ->actions([
                NotificationAction::make('view')
                    ->label('View Campaign')
                    ->url($viewUrl)
                    ->markAsRead(),
            ])
            ->sendToDatabase($creator);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function getCampaignUrl(RecruitmentCampaign $campaign): string
    {
        return RecruitmentCampaignResource::getUrl('view', ['record' => $campaign]);
    }
}
