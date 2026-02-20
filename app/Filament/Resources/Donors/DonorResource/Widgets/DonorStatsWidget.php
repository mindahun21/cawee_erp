<?php

namespace App\Filament\Resources\Donors\DonorResource\Widgets;

use App\Models\Donor;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DonorStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalDonors = Donor::count();
        $individualDonors = Donor::where('donor_type', 'individual')->count();
        $organizationDonors = Donor::whereIn('donor_type', ['corporate', 'foundation'])->count();
        $activeDonors = Donor::where('status', 'active')->count();
        
        $activeRate = $totalDonors > 0 ? round(($activeDonors / $totalDonors) * 100, 1) : 0;

        return [
            Stat::make('Total Donors', number_format($totalDonors))
                ->description('All registered donors')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
            Stat::make('Individuals', number_format($individualDonors))
                ->description('Personal contributors')
                ->descriptionIcon('heroicon-m-user')
                ->color('info'),
            Stat::make('Organizations', number_format($organizationDonors))
                ->description('Corporate & Foundations')
                ->descriptionIcon('heroicon-m-building-office')
                ->color('success'),
            Stat::make('Active Rate', $activeRate . '%')
                ->description($activeDonors . ' active donors')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($activeRate > 80 ? 'success' : 'warning'),
        ];
    }
}
