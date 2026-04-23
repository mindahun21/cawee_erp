<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Recruitment\RecruitmentCampaign;

class CloseExpiredRecruitmentCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recruitment:close-expired-campaigns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically close recruitment campaigns whose end date has passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Finding expired active recruitment campaigns...');

        $expiredCampaigns = RecruitmentCampaign::where('status', RecruitmentCampaign::STATUS_ACTIVE)
            ->whereNotNull('end_date')
            ->where('end_date', '<', now()->startOfDay())
            ->get();

        if ($expiredCampaigns->isEmpty()) {
            $this->info('No expired campaigns found.');
            return;
        }

        foreach ($expiredCampaigns as $campaign) {
            $campaign->update([
                'status' => RecruitmentCampaign::STATUS_CLOSED,
            ]);
            $this->line("Closed campaign: {$campaign->title} (ID: {$campaign->id})");
        }

        $this->info(count($expiredCampaigns) . ' campaign(s) closed successfully.');
    }
}
