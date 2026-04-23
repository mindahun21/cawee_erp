<?php

namespace App\Filament\Widgets\Recruitment;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RecruitmentStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $nonClosedPlans = \App\Models\Recruitment\RecruitmentPlan::where('status', '!=', 'Closed');
        
        $totalVacancies = (clone $nonClosedPlans)->sum('vacancies_needed');
        $approvedPlans = (clone $nonClosedPlans)->where('status', 'Approved')->count();
        $pendingPlans = (clone $nonClosedPlans)->where('status', 'Submitted')->count();
        $activeChannels = \App\Models\Recruitment\RecruitmentChannel::where('status', 'active')->count();

        return [
            Stat::make('Total Vacancies', $totalVacancies)
                ->description('Across all active plans')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Approved Plans', $approvedPlans)
                ->description('Ready for sourcing')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
            Stat::make('Pending Approvals', $pendingPlans)
                ->description('Awaiting workflow action')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            Stat::make('Active Channels', $activeChannels)
                ->description('Current sourcing platforms')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('info'),
        ];
    }
}
