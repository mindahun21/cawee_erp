<?php

namespace App\Filament\Resources\Campaigns\CampaignResource\Widgets;

use App\Models\Campaign;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CampaignStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Campaigns', Campaign::count())
                ->description('Total campaigns in the system')
                ->descriptionIcon('heroicon-m-rectangle-stack')
                ->color('info'),
            Stat::make('Active Campaigns', Campaign::where('status', 'active')->count())
                ->description('Campaigns currently running')
                ->descriptionIcon('heroicon-m-play')
                ->color('success'),
            Stat::make('Total Goal Amount', '$' . number_format(Campaign::sum('goal_amount'), 2))
                ->description('Combined goal of all campaigns')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),
        ];
    }
}
