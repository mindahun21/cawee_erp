<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Recruitment\RecruitmentPlan;
use App\Models\Recruitment\RecruitmentCampaign;
use App\Models\Recruitment\RecruitmentOffer;
use App\Models\Recruitment\RecruitmentInterviewSchedule;

class RecruitmentDailyMaintenance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recruitment:daily-maintenance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Performs daily maintenance tasks like expiring old plans, campaigns, offers, and schedules.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now()->startOfDay();

        // 1. Expire Plans -> Closed
        RecruitmentPlan::query()
            ->whereNotNull('end_date')
            ->where('end_date', '<', $today)
            ->whereIn('status', [RecruitmentPlan::STATUS_SUBMITTED, RecruitmentPlan::STATUS_APPROVED])
            ->get()
            ->each(fn ($plan) => $plan->update(['status' => RecruitmentPlan::STATUS_CLOSED]));

        // 2. Expire Campaigns -> Paused
        RecruitmentCampaign::query()
            ->whereNotNull('end_date')
            ->where('end_date', '<', $today)
            ->whereIn('status', [
                RecruitmentCampaign::STATUS_SUBMITTED,
                RecruitmentCampaign::STATUS_ACTIVE,
                RecruitmentCampaign::STATUS_FULL
            ])
            ->get()
            ->each(fn ($campaign) => $campaign->update(['status' => RecruitmentCampaign::STATUS_PAUSED]));

        // 3. Expire Offers -> Expired
        RecruitmentOffer::query()
            ->whereNotNull('offer_expiry_date')
            ->where('offer_expiry_date', '<', $today)
            ->whereIn('status', [RecruitmentOffer::STATUS_SUBMITTED, RecruitmentOffer::STATUS_APPROVED])
            ->get()
            ->each(fn ($offer) => $offer->update(['status' => RecruitmentOffer::STATUS_EXPIRED]));

        // 4. Expire Interview Schedules -> Completed
        RecruitmentInterviewSchedule::query()
            ->whereNotNull('interview_date')
            ->where('interview_date', '<', $today)
            ->whereIn('status', [
                RecruitmentInterviewSchedule::STATUS_SUBMITTED,
                RecruitmentInterviewSchedule::STATUS_SCHEDULED
            ])
            ->get()
            ->each(fn ($schedule) => $schedule->update(['status' => RecruitmentInterviewSchedule::STATUS_COMPLETED]));

        $this->info('Daily recruitment maintenance completed successfully.');
    }
}
