<?php

namespace App\Filament\Resources\Campaigns\CampaignResource\Widgets;

use App\Models\Campaign;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

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
            Stat::make('Total Goal (Est. ETB)', 'ETB ' . number_format(Campaign::sum(\Illuminate\Support\Facades\DB::raw('COALESCE(base_goal_amount, goal_amount)')), 2))
                ->description('Combined goal (converted to ETB using daily exchange rate)')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning'),
        ];
    }
}
