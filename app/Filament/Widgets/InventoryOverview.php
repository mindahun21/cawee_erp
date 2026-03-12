<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\Cache;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $metrics = Cache::remember('dashboard:inventory-overview', now()->addMinutes(5), fn (): array => [
            'total_assets' => \App\Models\Asset::count(),
            'total_investment' => \App\Models\Asset::sum('purchase_cost'),
            'active_assignments' => \App\Models\AssetAssignment::whereNull('returned_date')->count(),
        ]);

        return [
            Stat::make('Total Assets', $metrics['total_assets'])
                ->description('Total items in inventory')
                ->descriptionIcon('heroicon-m-briefcase'),
            Stat::make('Total Investment', 'INR ' . number_format($metrics['total_investment'], 2))
                ->description('Total purchase cost')
                ->descriptionIcon('heroicon-m-currency-rupee')
                ->color('success'),

            Stat::make('Active Assignments', $metrics['active_assignments'])
                ->description('Assets currently checked out')
                ->descriptionIcon('heroicon-m-user'),
        ];
    }
}
