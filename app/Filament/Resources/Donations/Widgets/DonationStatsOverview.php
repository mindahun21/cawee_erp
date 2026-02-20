<?php

namespace App\Filament\Resources\Donations\Widgets;

use App\Services\DonationService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DonationStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $service = app(DonationService::class);
        $statistics = $service->getDonationStatistics();
        $overall = $statistics['overall'];
        $currentYear = $service->getYearlyComparison()['current_year'];

        return [
            Stat::make('Total Donations', number_format($overall['total_donations']))
                ->description('All time')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Total Amount Raised', '$' . number_format($overall['total_amount'], 2))
                ->description('All time')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),

            Stat::make('This Year', '$' . number_format($currentYear['total_amount'], 2))
                ->description('Year to date')
                ->descriptionIcon('heroicon-m-calendar')
                ->color('info')
                ->chart([$currentYear['total_amount']]), // Placeholder for sparkline if we had monthly data readily available as array
        ];
    }
}
