<?php

namespace App\Console\Commands;

use App\Models\Recruitment\RecruitmentOffer;
use App\Models\Recruitment\RecruitmentApplication;
use App\Services\Recruitment\RecruitmentApplicationService;
use Illuminate\Console\Command;

class ExpireRecruitmentOffers extends Command
{
    protected $signature   = 'recruitment:expire-offers';
    protected $description = 'Mark recruitment offers as expired if their expiry date has passed and they have not been responded to.';

    public function handle(): void
    {
        $expiredOffers = RecruitmentOffer::query()
            ->whereIn('status', [
                RecruitmentOffer::STATUS_APPROVED,
                RecruitmentOffer::STATUS_SUBMITTED,
            ])
            ->whereNotNull('offer_expiry_date')
            ->whereDate('offer_expiry_date', '<', today())
            ->get();

        $count = $expiredOffers->count();
        $service = app(\App\Services\Recruitment\RecruitmentApplicationService::class);

        foreach ($expiredOffers as $offer) {
            $offer->update(['status' => RecruitmentOffer::STATUS_EXPIRED]);
            
            if ($offer->application && $offer->application->status === \App\Models\Recruitment\RecruitmentApplication::STATUS_OFFER_PENDING) {
                try {
                    $service->transition(
                        $offer->application, 
                        \App\Models\Recruitment\RecruitmentApplication::STATUS_SELECTED, 
                        null, 
                        'Offer expired automatically'
                    );
                } catch (\Exception $e) {
                    $this->error("Failed to transition application {$offer->application_id}: " . $e->getMessage());
                }
            }
        }

        $this->info("Expired {$count} recruitment offer(s).");
    }
}
