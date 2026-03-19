<?php

namespace App\Filament\Widgets\Recruitment;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RecruitmentStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $activePlans = \App\Models\Recruitment\RecruitmentPlan::where('status', '!=', 'closed')->count();
        $totalVacancies = \App\Models\Recruitment\RecruitmentPlan::where('status', '!=', 'closed')->sum('vacancies_needed');
        $skillsCount = \App\Models\Recruitment\RecruitmentSkill::count();

        return [
            Stat::make('Active Recruitment Plans', $activePlans)
                ->description('Currently ongoing plans')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
            Stat::make('Total Vacancies to Fill', $totalVacancies)
                ->description('Sum of all open positions')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),
            Stat::make('Registered Skills', $skillsCount)
                ->description('Unique skills tracked')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('info'),
        ];
    }
}
