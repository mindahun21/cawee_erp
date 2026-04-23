<?php

namespace App\Observers\Recruitment;

use App\Models\Recruitment\RecruitmentApplication;
use Filament\Notifications\Notification;

class RecruitmentApplicationObserver
{
    /**
     * Handle the RecruitmentApplication "updated" event.
     */
    public function updated(RecruitmentApplication $application): void
    {
        if ($application->isDirty('status')) {
            $status = $application->status;
            
            $candidateMessage = match ($status) {
                RecruitmentApplication::STATUS_SHORTLISTED => 'Great news! Your application is now Under Consideration.',
                RecruitmentApplication::STATUS_UNDER_REVIEW => 'Your application is currently Under Review by our team.',
                RecruitmentApplication::STATUS_REJECTED => 'Thank you for applying. At this time, we have decided to proceed with other candidates.',
                RecruitmentApplication::STATUS_HIRED => 'Congratulations! You have been selected for this position.',
                default => "Your application status has been updated.",
            };


            // Notify the Campaign Manager
            $application->loadMissing(['candidate', 'campaign.manager']);
            $recipient = $application->campaign?->manager;

            if ($recipient) {
                $candidateName = $application->candidate?->full_name ?? 'Unknown Candidate';

                Notification::make()
                    ->title("Application Update")
                    ->body("Candidate {$candidateName}'s status changed to: " . strtoupper($status))
                    ->info()
                    ->actions([
                        \Filament\Actions\Action::make('view')
                            ->label('View Application')
                            ->url(\App\Filament\Resources\Recruitment\RecruitmentApplications\RecruitmentApplicationResource::getUrl('view', ['record' => $application])),
                    ])
                    ->sendToDatabase($recipient);
            }
        }
    }
}
