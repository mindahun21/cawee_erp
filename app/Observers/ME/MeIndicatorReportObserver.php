<?php

namespace App\Observers\ME;

use App\Models\ME\MeIndicatorReport;
use App\Services\ME\AlertService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

class MeIndicatorReportObserver
{
    private static ?bool $canSyncAlerts = null;

    public function saved(MeIndicatorReport $report): void
    {
        if (! $this->canSyncAlerts()) {
            return;
        }

        try {
            app(AlertService::class)->syncForReport($report);
        } catch (QueryException) {
            // Keep report import/create flow working even if alert tables are not ready yet.
        }
    }

    private function canSyncAlerts(): bool
    {
        if (self::$canSyncAlerts !== null) {
            return self::$canSyncAlerts;
        }

        self::$canSyncAlerts = Schema::hasTable('me_alerts')
            && Schema::hasTable('me_alert_rules');

        return self::$canSyncAlerts;
    }
}
