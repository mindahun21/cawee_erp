<?php

namespace App\Console\Commands;

use App\Models\Recruitment\RecruitmentOffer;
use Illuminate\Console\Command;

class ExpireRecruitmentOffers extends Command
{
    protected $signature   = 'recruitment:expire-offers';
    protected $description = 'Mark recruitment offers as expired if their expiry date has passed and they have not been responded to.';

    public function handle(): void
    {
        $count = RecruitmentOffer::whereIn('status', [
                RecruitmentOffer::STATUS_APPROVED,
                RecruitmentOffer::STATUS_SUBMITTED, // edge case: never approved but past expiry
            ])
            ->whereNotNull('offer_expiry_date')
            ->whereDate('offer_expiry_date', '<', today())
            ->update(['status' => RecruitmentOffer::STATUS_EXPIRED]);

        $this->info("Expired {$count} recruitment offer(s).");
    }
}
