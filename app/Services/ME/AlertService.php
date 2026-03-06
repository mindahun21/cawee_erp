<?php

namespace App\Services\ME;

use App\Models\ME\MeAlert;
use App\Models\ME\MeAlertRule;
use App\Models\ME\MeIndicatorReport;

class AlertService
{
    public function __construct(
        private readonly PerformanceService $performanceService,
    ) {
    }

    public function syncForReport(MeIndicatorReport $report): void
    {
        $report->loadMissing('indicator');

        if (! $report->indicator) {
            return;
        }

        $target = $report->resolvedTargetValue();
        $actual = (float) $report->actual_value;
        $progress = $this->performanceService->computeProgress($actual, $target);

        MeAlert::query()
            ->where('indicator_id', $report->indicator_id)
            ->where('report_id', $report->id)
            ->delete();

        $warningThreshold = (float) $report->indicator->threshold_warning;
        $criticalThreshold = (float) $report->indicator->threshold_critical;

        if ($progress < $criticalThreshold) {
            $this->createAlert($report, 'critical', sprintf(
                'Critical underperformance: %.2f%% progress is below critical threshold %.2f%%.',
                $progress,
                $criticalThreshold
            ));

            return;
        }

        if ($progress < $warningThreshold) {
            $this->createAlert($report, 'warning', sprintf(
                'Low performance warning: %.2f%% progress is below warning threshold %.2f%%.',
                $progress,
                $warningThreshold
            ));
        }

        $this->applyActiveRules($report, $progress, $target, $actual);
    }

    public function recomputeAll(): int
    {
        $count = 0;

        MeIndicatorReport::query()
            ->orderBy('id')
            ->chunkById(100, function ($reports) use (&$count): void {
                foreach ($reports as $report) {
                    $this->syncForReport($report);
                    $count++;
                }
            });

        return $count;
    }

    private function applyActiveRules(MeIndicatorReport $report, float $progress, float $target, float $actual): void
    {
        $variancePercent = $target <= 0 ? 0.0 : round((($target - $actual) / $target) * 100, 2);

        MeAlertRule::query()
            ->where('is_active', true)
            ->get()
            ->each(function (MeAlertRule $rule) use ($report, $progress, $variancePercent): void {
                if ($rule->condition === 'below_percent') {
                    $this->applyPercentRule($rule, $report, $progress);

                    return;
                }

                if ($rule->condition === 'below_variance') {
                    $this->applyVarianceRule($rule, $report, $variancePercent);
                }
            });
    }

    private function applyPercentRule(MeAlertRule $rule, MeIndicatorReport $report, float $progress): void
    {
        $criticalThreshold = $rule->critical_threshold !== null ? (float) $rule->critical_threshold : null;
        $warningThreshold = $rule->warning_threshold !== null ? (float) $rule->warning_threshold : null;

        if (($criticalThreshold !== null) && $progress < $criticalThreshold) {
            $this->createAlert(
                $report,
                'critical',
                sprintf('[%s] Progress %.2f%% is below critical threshold %.2f%%.', $rule->name, $progress, $criticalThreshold)
            );

            return;
        }

        if (($warningThreshold !== null) && $progress < $warningThreshold) {
            $this->createAlert(
                $report,
                'warning',
                sprintf('[%s] Progress %.2f%% is below warning threshold %.2f%%.', $rule->name, $progress, $warningThreshold)
            );
        }
    }

    private function applyVarianceRule(MeAlertRule $rule, MeIndicatorReport $report, float $variancePercent): void
    {
        $criticalThreshold = $rule->critical_threshold !== null ? (float) $rule->critical_threshold : null;
        $warningThreshold = $rule->warning_threshold !== null ? (float) $rule->warning_threshold : null;

        if (($criticalThreshold !== null) && $variancePercent > $criticalThreshold) {
            $this->createAlert(
                $report,
                'critical',
                sprintf(
                    '[%s] Variance %.2f%% exceeds critical threshold %.2f%%.',
                    $rule->name,
                    $variancePercent,
                    $criticalThreshold
                )
            );

            return;
        }

        if (($warningThreshold !== null) && $variancePercent > $warningThreshold) {
            $this->createAlert(
                $report,
                'warning',
                sprintf(
                    '[%s] Variance %.2f%% exceeds warning threshold %.2f%%.',
                    $rule->name,
                    $variancePercent,
                    $warningThreshold
                )
            );
        }
    }

    private function createAlert(MeIndicatorReport $report, string $severity, string $message): void
    {
        MeAlert::query()->create([
            'indicator_id' => $report->indicator_id,
            'report_id' => $report->id,
            'severity' => $severity,
            'message' => $message,
        ]);
    }
}
