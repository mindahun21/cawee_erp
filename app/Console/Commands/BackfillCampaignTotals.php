<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Console\Command;

class BackfillCampaignTotals extends Command
{
    protected $signature = 'campaigns:backfill-totals';

    protected $description = 'Recalculate and sync total_raised and donor_count for all campaigns from actual donation data.';

    public function handle(): void
    {
        $campaigns = Campaign::withoutGlobalScopes()->pluck('id');

        $this->info("Backfilling totals for {$campaigns->count()} campaign(s)...");

        $bar = $this->output->createProgressBar($campaigns->count());
        $bar->start();

        foreach ($campaigns as $campaignId) {
            $stats = Donation::where('campaign_id', $campaignId)
                ->where('status', 'completed')
                ->selectRaw('
                    SUM(COALESCE(base_amount, amount)) as total_raised,
                    COUNT(DISTINCT donor_id) as donor_count
                ')
                ->first();

            Campaign::withoutGlobalScopes()->where('id', $campaignId)->update([
                'total_raised' => $stats->total_raised ?? 0,
                'donor_count'  => $stats->donor_count ?? 0,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done! All campaign totals have been recalculated from actual donation data.');
    }
}
