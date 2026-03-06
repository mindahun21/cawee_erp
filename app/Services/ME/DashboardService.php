<?php

namespace App\Services\ME;

use App\Models\ME\MeIndicator;
use App\Models\ME\MeIndicatorReport;
use App\Models\ME\MeIndicatorTarget;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardService
{
    public function __construct(
        private readonly PerformanceService $performanceService,
    ) {
    }

    public function kpis(array $filters = []): array
    {
        $rows = $this->indicatorPerformance($filters);
        $total = $rows->count();
        $reported = $rows->whereNotNull('last_reported_date');
        $reportRowsQuery = MeIndicatorReport::query();
        $this->applyReportFilters($reportRowsQuery, $filters);
        $totalReportRows = (int) $reportRowsQuery->count();

        $onTrack = $reported->where('status', 'on_track')->count();
        $needsAttention = $reported->where('status', 'needs_attention')->count();
        $offTrack = $reported->where('status', 'off_track')->count();

        return [
            'total_indicators' => $total,
            'reported_this_period' => $reported->count(),
            'total_report_rows' => $totalReportRows,
            'on_track' => $onTrack,
            'needs_attention' => $needsAttention,
            'off_track' => $offTrack,
            'coverage_rate' => $total > 0 ? round(($reported->count() / $total) * 100, 2) : 0.0,
        ];
    }

    public function indicatorPerformance(array $filters = []): Collection
    {
        $frameworkType = $filters['framework_type'] ?? null;
        $location = $filters['location'] ?? null;

        $indicators = MeIndicator::query()
            ->when($frameworkType, fn (Builder $query) => $query->where('framework_type', $frameworkType))
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return $indicators->map(function (MeIndicator $indicator) use ($filters, $location): array {
            $latestReport = $this->latestReportForIndicator($indicator, $filters);
            $latestTarget = $this->latestTargetForIndicator($indicator, $latestReport, $filters, $location);

            $target = (float) ($latestTarget?->target_value ?? 0);
            $actual = (float) ($latestReport?->actual_value ?? 0);
            $progress = $this->performanceService->computeProgress($actual, $target);
            $status = $this->performanceService->statusFromProgress($progress);

            return [
                'indicator_id' => $indicator->id,
                'code' => $indicator->code,
                'name' => $indicator->name,
                'framework_type' => $indicator->framework_type,
                'latest_target' => $target,
                'latest_actual' => $actual,
                'progress_percent' => $progress,
                'status' => $status,
                'last_reported_date' => $latestReport?->period_end?->toDateString(),
                'scope_location' => $latestReport?->scope_location,
            ];
        });
    }

    public function frameworkProgressChart(array $filters = []): array
    {
        $rows = $this->indicatorPerformance($filters);
        $frameworks = ['output', 'outcome', 'impact'];

        $targetData = [];
        $actualData = [];

        foreach ($frameworks as $framework) {
            $subset = $rows->where('framework_type', $framework);
            $targetData[] = round((float) $subset->sum('latest_target'), 2);
            $actualData[] = round((float) $subset->sum('latest_actual'), 2);
        }

        return [
            'labels' => ['Output', 'Outcome', 'Impact'],
            'target' => $targetData,
            'actual' => $actualData,
        ];
    }

    public function performanceTrend(array $filters = []): array
    {
        $query = MeIndicatorReport::query()->with('indicator');

        $this->applyReportFilters($query, $filters);

        $rows = $query->orderBy('period_end')->get();

        $grouped = $rows
            ->groupBy(fn (MeIndicatorReport $report): string => Carbon::parse($report->period_end)->format('Y-m'))
            ->map(function (Collection $group): float {
                $totalTarget = 0.0;
                $totalActual = 0.0;

                foreach ($group as $report) {
                    $totalActual += (float) $report->actual_value;
                    $totalTarget += $report->resolvedTargetValue();
                }

                return $this->performanceService->computeProgress($totalActual, $totalTarget);
            });

        return [
            'labels' => $grouped->keys()->values()->all(),
            'data' => $grouped->values()->map(fn ($value) => round((float) $value, 2))->all(),
        ];
    }

    public function locationProgress(array $filters = []): Collection
    {
        $query = MeIndicatorReport::query()->with('indicator');
        $this->applyReportFilters($query, $filters);

        return $query
            ->whereNotNull('scope_location')
            ->get()
            ->groupBy('scope_location')
            ->map(function (Collection $reports, string $location): array {
                $average = $reports->avg(fn (MeIndicatorReport $report): float => $report->progressPercent());

                return [
                    'scope_location' => $location,
                    'average_progress' => round((float) $average, 2),
                    'reports_count' => $reports->count(),
                ];
            })
            ->sortByDesc('average_progress')
            ->values();
    }

    private function latestReportForIndicator(MeIndicator $indicator, array $filters): ?MeIndicatorReport
    {
        $query = MeIndicatorReport::query()->where('indicator_id', $indicator->id);
        $this->applyReportFilters($query, $filters);

        return $query
            ->orderByDesc('period_end')
            ->orderByDesc('id')
            ->first();
    }

    private function latestTargetForIndicator(
        MeIndicator $indicator,
        ?MeIndicatorReport $report,
        array $filters,
        ?string $location,
    ): ?MeIndicatorTarget {
        $query = $indicator->targets()->newQuery()->where('indicator_id', $indicator->id);

        if ($report) {
            $query->whereDate('period_start', '<=', $report->period_end)
                ->whereDate('period_end', '>=', $report->period_start)
                ->where(function (Builder $scopedQuery) use ($report): void {
                    if ($report->scope_location) {
                        $scopedQuery->whereNull('scope_location')
                            ->orWhere('scope_location', $report->scope_location);

                        return;
                    }

                    $scopedQuery->whereNull('scope_location');
                })
                ->where(function (Builder $scopedQuery) use ($report): void {
                    if ($report->scope_project) {
                        $scopedQuery->whereNull('scope_project')
                            ->orWhere('scope_project', $report->scope_project);

                        return;
                    }

                    $scopedQuery->whereNull('scope_project');
                });
        } else {
            if ($location) {
                $query->where(function (Builder $scopedQuery) use ($location): void {
                    $scopedQuery->whereNull('scope_location')
                        ->orWhere('scope_location', $location);
                });
            }

            if (! empty($filters['date_from'])) {
                $query->whereDate('period_end', '>=', $filters['date_from']);
            }

            if (! empty($filters['date_to'])) {
                $query->whereDate('period_start', '<=', $filters['date_to']);
            }
        }

        return $query
            ->orderByRaw('CASE WHEN scope_location IS NULL THEN 1 ELSE 0 END')
            ->orderByRaw('CASE WHEN scope_project IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('period_end')
            ->first();
    }

    private function applyReportFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['date_from'])) {
            $query->whereDate('period_end', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('period_start', '<=', $filters['date_to']);
        }

        if (! empty($filters['location'])) {
            $query->where('scope_location', $filters['location']);
        }

        if (! empty($filters['framework_type'])) {
            $query->whereHas('indicator', function (Builder $indicatorQuery) use ($filters): void {
                $indicatorQuery->where('framework_type', $filters['framework_type']);
            });
        }
    }
}
