<?php

namespace App\Filament\Resources\Warehouses\Widgets;

use App\Models\Warehouse;
use App\Models\WarehouseType;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WarehouseStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalCount = Warehouse::count();
        $activeCount = Warehouse::where('is_active', true)->count();
        $inactiveCount = Warehouse::where('is_active', false)->count();
        
        $topType = WarehouseType::withCount('warehouses')
            ->orderByDesc('warehouses_count')
            ->first();

        return [
            Stat::make('Total Warehouses', $totalCount)
                ->description('Total storage facilities')
                ->icon('heroicon-o-home-modern'),
            Stat::make('Active Locations', $activeCount)
                ->description('Operational and online')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Inactive / Maintenance', $inactiveCount)
                ->description('Currently offline')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
            Stat::make('Primary Type', $topType?->name ?? 'N/A')
                ->description('Most common facility type')
                ->icon('heroicon-o-tag')
                ->color('info'),
        ];
    }
}
