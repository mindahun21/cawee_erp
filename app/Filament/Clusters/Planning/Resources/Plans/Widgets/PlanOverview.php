<?php

namespace App\Filament\Clusters\Planning\Resources\Plans\Widgets;

use App\Models\Plan;
use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlanOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Active Plans', Plan::where('status', 'active')->count())
                ->description('Total active hierarchical plans')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('success'),
            Stat::make('Overdue Tasks', Task::where('status', '!=', 'completed')->where('deadline', '<', now())->count())
                ->description('Tasks past their deadline')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
            Stat::make('Total Progress', round(Plan::avg('progress_percentage') ?? 0, 1) . '%')
                ->description('Average completion across all plans')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}
