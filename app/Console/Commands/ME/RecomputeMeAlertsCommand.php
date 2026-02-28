<?php

namespace App\Console\Commands\ME;

use App\Services\ME\AlertService;
use Illuminate\Console\Command;

class RecomputeMeAlertsCommand extends Command
{
    protected $signature = 'me:recompute-alerts';

    protected $description = 'Recompute M&E alerts based on current report performance and thresholds.';

    public function handle(AlertService $alertService): int
    {
        $count = $alertService->recomputeAll();

        $this->info("Recomputed alerts for {$count} report(s).");

        return self::SUCCESS;
    }
}
