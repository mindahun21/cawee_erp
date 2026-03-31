<?php

namespace App\Observers\Recruitment;

use App\Filament\Resources\Recruitment\RecruitmentPlans\RecruitmentPlanResource;
use App\Mail\Recruitment\RecruitmentPlanApprovedMail;
use App\Mail\Recruitment\RecruitmentPlanRejectedMail;
use App\Mail\Recruitment\RecruitmentPlanSubmittedMail;
use App\Models\Recruitment\RecruitmentPlan;
use App\Models\User;
use App\Services\Recruitment\RecruitmentApprovalService;
use Filament\Actions\Action as NotificationAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RecruitmentPlanObserver
{
    /**
     * Handle the RecruitmentPlan "updated" event.
     */
    public function updated(RecruitmentPlan $plan): void
    {
        // Only react if status actually changed
        if (! $plan->wasChanged('status')) {
            return;
        }

        try {
            $this->handleStatusChange(
                $plan,
                $plan->getOriginal('status'),
                $plan->status
            );
        } catch (\Throwable $e) {
            Log::error('RecruitmentPlanObserver: failed to handle status change', [
                'plan_id'    => $plan->id,
                'old_status' => $plan->getOriginal('status'),
                'new_status' => $plan->status,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    private function handleStatusChange(RecruitmentPlan $plan, string $from, string $to): void
    {
        // Rejection now reverts status to Draft (not Rejected),
        // so we detect a reject-to-draft transition.
        if ($from === RecruitmentPlan::STATUS_SUBMITTED && $to === RecruitmentPlan::STATUS_DRAFT) {
            $this->notifyPlanRejected($plan);
            return;
        }

        match ($to) {
            RecruitmentPlan::STATUS_SUBMITTED => $this->notifyPlanSubmitted($plan),
            RecruitmentPlan::STATUS_APPROVED  => $this->notifyPlanApproved($plan),
            default                           => null,
        };
    }

    // ── Plan submitted → notify ONLY the first stage approver role ───────

    private function notifyPlanSubmitted(RecruitmentPlan $plan): void
    {
        $plan->loadMissing(['jobPosition', 'department', 'creator']);
        $viewUrl = $this->getPlanUrl($plan);

        $nextPending = RecruitmentApprovalService::nextPendingRecord($plan, 'recruitment_plan');

        if (! $nextPending) {
            return;
        }

        $approvers = User::role($nextPending->required_role)->get();

        foreach ($approvers as $approver) {
            Mail::to($approver->email)->queue(new RecruitmentPlanSubmittedMail($plan, $viewUrl));

            FilamentNotification::make()
                ->title('Recruitment Plan Awaiting Approval')
                ->body("Plan for {$plan->jobPosition->title} ({$plan->department->name}) submitted by {$plan->creator->name}.")
                ->icon('heroicon-o-document-text')
                ->iconColor('warning')
                ->actions([
                    NotificationAction::make('review')
                        ->label('Review Plan')
                        ->url($viewUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($approver);
        }
    }

    // ── Plan fully approved → notify the creator only ─────────────────

    private function notifyPlanApproved(RecruitmentPlan $plan): void
    {
        $plan->loadMissing(['jobPosition', 'department', 'creator']);
        $creator = $plan->creator;

        if (! $creator) {
            return;
        }

        $viewUrl = $this->getPlanUrl($plan);

        Mail::to($creator->email)->queue(new RecruitmentPlanApprovedMail($plan, $viewUrl));

        FilamentNotification::make()
            ->title('Recruitment Plan Approved')
            ->body("Your recruitment plan for {$plan->jobPosition->title} has been approved.")
            ->icon('heroicon-o-check-circle')
            ->iconColor('success')
            ->actions([
                NotificationAction::make('view')
                    ->label('View Plan')
                    ->url($viewUrl)
                    ->markAsRead(),
            ])
            ->sendToDatabase($creator);
    }

    // ── Plan rejected → notify the creator only ─────────────────────

    private function notifyPlanRejected(RecruitmentPlan $plan): void
    {
        $plan->loadMissing(['jobPosition', 'department', 'creator']);
        $creator = $plan->creator;

        if (! $creator) {
            return;
        }

        $viewUrl = $this->getPlanUrl($plan);

        $rejectionNotes = RecruitmentApprovalService::previousRejectionNotes($plan)
            ?? 'No reason provided.';

        $plan->notes = $rejectionNotes;

        Mail::to($creator->email)->queue(new RecruitmentPlanRejectedMail($plan, $viewUrl));

        FilamentNotification::make()
            ->title('Recruitment Plan Returned for Revision')
            ->body("Your recruitment plan for {$plan->jobPosition->title} was returned. Reason: {$rejectionNotes}")
            ->icon('heroicon-o-x-circle')
            ->iconColor('danger')
            ->actions([
                NotificationAction::make('view')
                    ->label('View Plan')
                    ->url($viewUrl)
                    ->markAsRead(),
            ])
            ->sendToDatabase($creator);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    private function getPlanUrl(RecruitmentPlan $plan): string
    {
        return RecruitmentPlanResource::getUrl('view', ['record' => $plan]);
    }
}
