<?php

namespace App\Observers;

use App\Models\Donation;
use App\Services\DonationService;

class DonationObserver
{
    protected $donationService;

    public function __construct(DonationService $donationService)
    {
        $this->donationService = $donationService;
    }

    /**
     * Handle the Donation "created" event.
     */
    public function created(Donation $donation): void
    {
        $this->updateStatistics($donation);
    }

    /**
     * Handle the Donation "updated" event.
     */
    public function updated(Donation $donation): void
    {
        // If critical fields changed (amount, donor, campaign, status), update stats
        if ($donation->wasChanged(['amount', 'donor_id', 'campaign_id', 'status'])) {
            $this->updateStatistics($donation);
            
            // If donor or campaign changed, we need to update the OLD donor/campaign too
            if ($donation->wasChanged('donor_id')) {
                $this->donationService->updateDonorStatistics($donation->getOriginal('donor_id'));
            }
            if ($donation->wasChanged('campaign_id') && $donation->getOriginal('campaign_id')) {
                $this->donationService->updateCampaignStatistics($donation->getOriginal('campaign_id'));
            }
        }
    }

    /**
     * Handle the Donation "deleted" event.
     */
    public function deleted(Donation $donation): void
    {
        $this->updateStatistics($donation);
    }

    /**
     * Handle the Donation "restored" event.
     */
    public function restored(Donation $donation): void
    {
        $this->updateStatistics($donation);
    }

    /**
     * Handle the Donation "force deleted" event.
     */
    public function forceDeleted(Donation $donation): void
    {
        $this->updateStatistics($donation);
    }

    protected function updateStatistics(Donation $donation): void
    {
        if ($donation->donor_id) {
            $this->donationService->updateDonorStatistics($donation->donor_id);
        }
        
        if ($donation->campaign_id) {
            $this->donationService->updateCampaignStatistics($donation->campaign_id);
        }
    }
}
