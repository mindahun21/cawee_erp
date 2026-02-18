<?php

namespace App\Filament\Resources\Donations\Widgets;

use App\Models\Donation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DonationStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Total donations
        $totalDonations = Donation::where('status', 'completed')->count();
        $totalAmount = Donation::where('status', 'completed')->sum('amount');

        // This month
        $thisMonthCount = Donation::where('status', 'completed')
            ->whereMonth('donation_date', now()->month)
            ->whereYear('donation_date', now()->year)
            ->count();
        
        $thisMonthAmount = Donation::where('status', 'completed')
            ->whereMonth('donation_date', now()->month)
            ->whereYear('donation_date', now()->year)
            ->sum('amount');

        // Last month for comparison
        $lastMonthAmount = Donation::where('status', 'completed')
            ->whereMonth('donation_date', now()->subMonth()->month)
            ->whereYear('donation_date', now()->subMonth()->year)
            ->sum('amount');

        $monthlyChange = $lastMonthAmount > 0 
            ? (($thisMonthAmount - $lastMonthAmount) / $lastMonthAmount) * 100 
            : 0;

        // Recurring donations
        $recurringCount = Donation::where('is_recurring', true)
            ->where('status', 'completed')
            ->count();
        
        $recurringAmount = Donation::where('is_recurring', true)
            ->where('status', 'completed')
            ->sum('amount');

        // Average donation
        $averageDonation = $totalDonations > 0 ? $totalAmount / $totalDonations : 0;

        return [
            Stat::make('Total Donations', number_format($totalAmount, 2))
                ->description($totalDonations . ' donations')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('This Month', number_format($thisMonthAmount, 2))
                ->description($thisMonthCount . ' donations')
                ->descriptionIcon($monthlyChange >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
                ->color($monthlyChange >= 0 ? 'success' : 'danger')
                ->chart([
                    $lastMonthAmount,
                    $thisMonthAmount,
                ]),

            Stat::make('Recurring Donations', $recurringCount)
                ->description('$' . number_format($recurringAmount, 2) . ' total')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('info'),

            Stat::make('Average Donation', number_format($averageDonation, 2))
                ->description('Per donation')
                ->descriptionIcon('heroicon-o-calculator')
                ->color('warning'),
        ];
    }
}
