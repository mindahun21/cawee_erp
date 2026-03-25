<?php

namespace App\Observers\Recruitment;

use App\Models\Recruitment\RecruitmentApplication;
use Filament\Notifications\Notification;
use App\Models\User;

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


            // In-App Notification to HR/Admins
            $hrUsers = User::role('super_admin')->get(); // Example fallback role
            
            if ($hrUsers->count() > 0) {
                Notification::make()
                    ->title("Application Update")
                    ->body("Candidate {$application->candidate->first_name} {$application->candidate->last_name}'s status changed to: " . strtoupper($status))
                    ->info()
                    ->sendToDatabase($hrUsers);
            }
        }
    }
}
