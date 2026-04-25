<?php

namespace App\Filament\Clusters\Planning\Pages;

use App\Filament\Clusters\Planning;
use App\Models\Plan;
use App\Models\Task;
use App\Models\PlanningKpi;
use App\Filament\Clusters\Planning\Resources\Plans\Widgets\PlanOverview;
use App\Filament\Clusters\Planning\Resources\Plans\Widgets\TasksChart;
use App\Filament\Clusters\Planning\Resources\Plans\Widgets\PlansByDeptChart;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use BackedEnum;

class PlanningDashboard extends Page
{
    protected static ?string $cluster = Planning::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected string $view = 'filament.clusters.planning.pages.planning-dashboard';

    protected static ?string $title = 'Planning Dashboard';

    protected static ?int $navigationSort = -1;

    public function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getWidgets(): array
    {
        return [
            TasksChart::class,
            PlansByDeptChart::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 3;
    }

    protected function getViewData(): array
    {
        $metrics = Cache::remember('dashboard:planning-view-data', now()->addMinutes(5), function (): array {
            return [
                'totalActivePlans' => Plan::where('status', 'active')->count(),
                'activeTasks' => Task::where('status', '!=', 'completed')->count(),
                'overdueTasks' => Task::where('status', '!=', 'completed')->where('deadline', '<', now())->count(),
                'completedTasksMonth' => Task::where('status', 'completed')
                    ->whereMonth('updated_at', now()->month)
                    ->whereYear('updated_at', now()->year)
                    ->count(),
                'avgProgress' => round(Plan::avg('progress_percentage') ?? 0, 1),
                'totalKpis' => PlanningKpi::count(),
                'underperformingKpis' => PlanningKpi::whereRaw('actual_value < target_value')->count(),
            ];
        });

        return $metrics;
    }
}
