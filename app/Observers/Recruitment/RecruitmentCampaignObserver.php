<?php

namespace App\Observers\Recruitment;

use App\Models\Recruitment\RecruitmentCampaign;
use Filament\Notifications\Notification;

class RecruitmentCampaignObserver
{
    /**
     * Handle the RecruitmentCampaign "updated" event.
     */
    public function updated(RecruitmentCampaign $campaign): void
    {
        if ($campaign->isDirty('status') && $campaign->status === RecruitmentCampaign::STATUS_ACTIVE) {
            
            // Notify followers
            $followers = $campaign->followers()->with('user')->get()->pluck('user')->filter();
            
            if ($followers->count() > 0) {
                Notification::make()
                    ->title("Campaign Activated: {$campaign->title}")
                    ->body("The campaign you are following is now active.")
                    ->info()
                    ->sendToDatabase($followers);
            }
        }
    }
}
