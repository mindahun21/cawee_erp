<?php

namespace App\Filament\Pages\ME;

use App\Traits\BelongsToModulePage;

use App\Filament\Widgets\ME\MeLocationMapPlaceholderWidget;
use App\Filament\Widgets\ME\MePerformanceTrendChartWidget;
use App\Filament\Widgets\ME\MeProgressByFrameworkChartWidget;
use App\Models\ME\MeIndicatorReport;
use App\Models\ME\MeProject;
use App\Services\ME\DashboardService;
use Illuminate\Support\Facades\Cache;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class MeDashboard extends Page
{
    use BelongsToModulePage;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string|UnitEnum|null $navigationGroup = 'Monitoring and Evaluation';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Dashboard';

    protected string $view = 'filament.pages.me.dashboard';

    public function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getWidgets(): array
    {
        return [
            MeProgressByFrameworkChartWidget::class,
            MePerformanceTrendChartWidget::class,
            MeLocationMapPlaceholderWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 3;
    }

    protected function getViewData(): array
    {
        return Cache::remember('dashboard:me-view-data', now()->addMinutes(5), function (): array {
            $kpis = app(DashboardService::class)->kpis();

            $projectsCount = MeProject::query()->count();
            $indicatorsCount = (int) ($kpis['total_indicators'] ?? 0);
            $reportedIndicators = (int) ($kpis['reported_this_period'] ?? 0);

            return [
                'projectsCount' => $projectsCount,
                'indicatorsCount' => $indicatorsCount,
                'reportsThisMonth' => MeIndicatorReport::query()
                    ->whereMonth('period_end', now()->month)
                    ->whereYear('period_end', now()->year)
                    ->count(),
                'needsAttention' => (int) ($kpis['needs_attention'] ?? 0),
                'coverageRate' => number_format((float) ($kpis['coverage_rate'] ?? 0), 2),
                'onTrack' => (int) ($kpis['on_track'] ?? 0),
                'offTrack' => (int) ($kpis['off_track'] ?? 0),
                'unreportedIndicators' => max($indicatorsCount - $reportedIndicators, 0),
                'reportRows' => (int) ($kpis['total_report_rows'] ?? 0),
                'reportedIndicators' => $reportedIndicators,
                'latestReportDate' => MeIndicatorReport::query()->max('period_end'),
            ];
        });
    }
}
