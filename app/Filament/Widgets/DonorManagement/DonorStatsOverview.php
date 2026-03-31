<?php

namespace App\Filament\Widgets\DonorManagement;

use App\Services\DonationService;
use App\Models\Donor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DonorStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $service = app(DonationService::class);
        $statistics = $service->getDonationStatistics();
        $overall = $statistics['overall'];
        
        $totalDonors = Donor::count();
        $activeDonors = Donor::where('status', 'active')->count();

        return [
            Stat::make('Total Funds Raised', 'ETB ' . number_format($overall['total_amount'], 2))
                ->description('All time completed donations')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Total Donors', number_format($totalDonors))
                ->description($activeDonors . ' active donors')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make('Average Donation', 'ETB ' . number_format($overall['average_amount'], 2))
                ->description('Per contribution')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),

            Stat::make('Active Campaigns', \App\Models\Campaign::where('status', 'active')->count())
                ->description('Fundraising initiatives')
                ->descriptionIcon('heroicon-m-flag')
                ->color('warning'),
        ];
    }
}
