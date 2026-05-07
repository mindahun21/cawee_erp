<?php

namespace App\Filament\Resources\Vehicles\Widgets;

use App\Models\Vehicle;
use App\Models\VehicleType;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VehicleStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $total       = Vehicle::count();
        $active      = Vehicle::where('is_active', true)->count();
        $inactive    = Vehicle::where('is_active', false)->count();
        $maintenance = Vehicle::whereHas('statusRecord', fn ($q) =>
            $q->whereIn('name', ['In Maintenance', 'Repairing'])
        )->count();
        $topType = VehicleType::withCount('vehicles')
            ->orderByDesc('vehicles_count')
            ->first();

        return [
            Stat::make('Total Fleet', $total)
                ->description('All registered vehicles')
                ->icon('heroicon-o-truck')
                ->color('primary'),
            Stat::make('Active Vehicles', $active)
                ->description('Currently operational')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('In Maintenance', $maintenance)
                ->description('Under repair / service')
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('warning'),
            Stat::make('Inactive / Decommissioned', $inactive)
                ->description('Offline or retired')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),
            Stat::make('Primary Fleet Type', $topType?->name ?? 'N/A')
                ->description('Most common vehicle type')
                ->icon('heroicon-o-tag')
                ->color('info'),
        ];
    }
}
