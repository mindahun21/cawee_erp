<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Assets', \App\Models\Asset::count())
                ->description('Total items in inventory')
                ->descriptionIcon('heroicon-m-briefcase'),
            Stat::make('Total Investment', 'INR ' . number_format(\App\Models\Asset::sum('purchase_cost'), 2))
                ->description('Total purchase cost')
                ->descriptionIcon('heroicon-m-currency-rupee')
                ->color('success'),

            Stat::make('Active Assignments', \App\Models\AssetAssignment::whereNull('returned_date')->count())
                ->description('Assets currently checked out')
                ->descriptionIcon('heroicon-m-user'),
        ];
    }
}
