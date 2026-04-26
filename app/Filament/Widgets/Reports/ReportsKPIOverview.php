<?php

namespace App\Filament\Widgets\Reports;

use App\Traits\BelongsToModuleWidget;

use App\Services\ReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ReportsKPIOverview extends BaseWidget
{
    use BelongsToModuleWidget;

    protected function getStats(): array
    {
        $service = app(ReportService::class);
        $kpis = $service->getDashboardKPIs();

        return [
            Stat::make('Total Funds Raised', '$' . number_format($kpis['total_funds_raised'], 2))
                ->description('$' . number_format($kpis['monthly_revenue'], 2) . ' this month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Active Donors', number_format($kpis['active_donors']))
                ->description($kpis['recurring_donors'] . ' recurring donors')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('primary'),

            Stat::make('Donor Retention', $kpis['donor_retention_rate'] . '%')
                ->description($kpis['donor_retention_rate'] >= 70 ? 'Excellent' : 'Needs improvement')
                ->descriptionIcon($kpis['donor_retention_rate'] >= 70 ? 'heroicon-m-check-badge' : 'heroicon-m-exclamation-triangle')
                ->color($kpis['donor_retention_rate'] >= 70 ? 'success' : 'warning'),

            Stat::make('Active Campaigns', $kpis['campaign_count'])
                ->description('$' . number_format($kpis['pledge_balance'], 2) . ' in pledges')
                ->descriptionIcon('heroicon-m-megaphone')
                ->color('info'),
        ];
    }
}
