<?php

namespace App\Observers\Recruitment;

use App\Filament\Resources\Recruitment\RecruitmentInterviewSchedules\RecruitmentInterviewScheduleResource;
use App\Mail\Recruitment\RecruitmentInterviewScheduleApprovedMail;
use App\Mail\Recruitment\RecruitmentInterviewScheduleRejectedMail;
use App\Mail\Recruitment\RecruitmentInterviewScheduleSubmittedMail;
use App\Notifications\Recruitment\InterviewAssignmentNotification;
use App\Notifications\Recruitment\InterviewInvitationNotification;
use App\Models\Recruitment\RecruitmentInterviewSchedule;
use App\Models\User;
use App\Services\Recruitment\RecruitmentApprovalService;
use Filament\Actions\Action as NotificationAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class RecruitmentInterviewScheduleObserver
{
    /**
     * Handle the RecruitmentInterviewSchedule "updated" event.
     */
    public function updated(RecruitmentInterviewSchedule $schedule): void
    {
        if (! $schedule->wasChanged('status')) {
            return;
        }

        try {
            $this->handleStatusChange(
                $schedule,
                $schedule->getOriginal('status'),
                $schedule->status
            );
        } catch (\Throwable $e) {
            Log::error('RecruitmentInterviewScheduleObserver: failed to handle status change', [
                'schedule_id' => $schedule->id,
                'old_status'  => $schedule->getOriginal('status'),
                'new_status'  => $schedule->status,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    private function handleStatusChange(RecruitmentInterviewSchedule $schedule, string $from, string $to): void
    {
        if ($from === RecruitmentInterviewSchedule::STATUS_SUBMITTED && $to === RecruitmentInterviewSchedule::STATUS_DRAFT) {
            $this->notifyScheduleRejected($schedule);
            return;
        }

        match ($to) {
            RecruitmentInterviewSchedule::STATUS_SUBMITTED => $this->notifyScheduleSubmitted($schedule),
            RecruitmentInterviewSchedule::STATUS_SCHEDULED => $this->notifyScheduleApproved($schedule),
            RecruitmentInterviewSchedule::STATUS_REJECTED  => $this->notifyScheduleRejected($schedule),
            default                                        => null,
        };
    }

    private function notifyScheduleSubmitted(RecruitmentInterviewSchedule $schedule): void
    {
        $schedule->loadMissing(['campaign', 'creator']);
        $viewUrl = $this->getScheduleUrl($schedule);

        $nextPending = RecruitmentApprovalService::nextPendingRecord($schedule, 'recruitment_interview_schedule');

        if (! $nextPending) {
            return;
        }

        $approvers = User::role($nextPending->required_role)->get();

        foreach ($approvers as $approver) {
            Mail::to($approver->email)->queue(new RecruitmentInterviewScheduleSubmittedMail($schedule, $viewUrl));

            FilamentNotification::make()
                ->title('Interview Schedule Awaiting Approval')
                ->body("Schedule '{$schedule->name}' for {$schedule->campaign->title} submitted by {$schedule->creator->name}.")
                ->icon('heroicon-o-document-text')
                ->iconColor('warning')
                ->actions([
                    NotificationAction::make('review')
                        ->label('Review Schedule')
                        ->url($viewUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($approver);
        }
    }

    private function notifyScheduleApproved(RecruitmentInterviewSchedule $schedule): void
    {
        $schedule->loadMissing(['campaign', 'creator', 'candidates', 'interviewers']);
        $creator = $schedule->creator;
        $viewUrl = $this->getScheduleUrl($schedule);

        if ($creator) {
            Mail::to($creator->email)->queue(new RecruitmentInterviewScheduleApprovedMail($schedule, $viewUrl));

            FilamentNotification::make()
                ->title('Interview Schedule Approved')
                ->body("Your interview schedule '{$schedule->name}' has been approved and published.")
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->actions([
                    NotificationAction::make('view')
                        ->label('View Schedule')
                        ->url($viewUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($creator);
        }

        // Notify Candidates
        foreach ($schedule->candidates as $candidate) {
            if ($candidate->email) {
                $slotStart = $candidate->pivot->candidate_from_time ?? $schedule->from_time;
                $slotEnd = $candidate->pivot->candidate_to_time ?? $schedule->to_time;
                
                Notification::route('mail', $candidate->email)
                    ->notify(new InterviewInvitationNotification($schedule, $candidate, $slotStart, $slotEnd));
            }
        }

        // Notify Interviewers
        foreach ($schedule->interviewers as $interviewer) {
            $role = $interviewer->pivot->role ?? 'interviewer';
            
            $interviewer->notify(new InterviewAssignmentNotification($schedule, $role, $viewUrl));
            
            FilamentNotification::make()
                ->title('New Interview Assignment')
                ->body("You have been assigned as {$role} for '{$schedule->name}'.")
                ->icon('heroicon-o-users')
                ->iconColor('success')
                ->actions([
                    NotificationAction::make('view')
                        ->label('View Schedule')
                        ->url($viewUrl)
                        ->markAsRead(),
                ])
                ->sendToDatabase($interviewer);
        }
    }

    private function notifyScheduleRejected(RecruitmentInterviewSchedule $schedule): void
    {
        $schedule->loadMissing(['campaign', 'creator']);
        $creator = $schedule->creator;

        if (! $creator) {
            return;
        }

        $viewUrl = $this->getScheduleUrl($schedule);
        $rejectionNotes = RecruitmentApprovalService::previousRejectionNotes($schedule)
            ?? 'No reason provided.';

        $schedule->notes = $rejectionNotes;

        Mail::to($creator->email)->queue(new RecruitmentInterviewScheduleRejectedMail($schedule, $viewUrl));

        FilamentNotification::make()
            ->title('Interview Schedule Returned for Revision')
            ->body("Your schedule '{$schedule->name}' was returned. Reason: {$rejectionNotes}")
            ->icon('heroicon-o-x-circle')
            ->iconColor('danger')
            ->actions([
                NotificationAction::make('view')
                    ->label('View Schedule')
                    ->url($viewUrl)
                    ->markAsRead(),
            ])
            ->sendToDatabase($creator);
    }

    private function getScheduleUrl(RecruitmentInterviewSchedule $schedule): string
    {
        return RecruitmentInterviewScheduleResource::getUrl('view', ['record' => $schedule]);
    }
}
