<?php

namespace App\Filament\Resources\Maintenances\Widgets;

use App\Models\Maintenance;
use App\Models\Asset;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MaintenanceStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        // Safety measure for low-memory environments
        @ini_set('memory_limit', '256M');

        $totalCostEtb = Maintenance::with('currency')->get()->sum(function ($m) {
            return (float) $m->cost * (float) ($m->currency?->exchange_rate ?? 1);
        });
        
        $assetMaintCount = Maintenance::whereHas('asset', function ($query) {
            $query->where('asset_tag', 'not like', 'VEH-%');
        })->count();

        $vehicleMaintCount = Maintenance::whereHas('asset', function ($query) {
            $query->where('asset_tag', 'like', 'VEH-%');
        })->count();

        $outOfServiceCount = Asset::whereHas('statusRecord', function ($query) {
            $query->where('name', 'Maintenance')
                  ->orWhere('name', 'Out of Service')
                  ->orWhere('name', 'Repair');
        })->count();

        $healthyCount = Asset::whereHas('statusRecord', function ($query) {
            $query->where('name', 'Available')
                  ->orWhere('name', 'Deployed')
                  ->orWhere('name', 'Assigned');
        })->count();

        return [
            Stat::make('Asset Maintenances', $assetMaintCount)
                ->description('General equipment records')
                ->icon('heroicon-o-wrench'),
            Stat::make('Vehicle Maintenances', $vehicleMaintCount)
                ->description('Fleet service records')
                ->icon('heroicon-o-truck'),
            Stat::make('Total Maintenance Cost', 'ETB ' . number_format($totalCostEtb, 2))
                ->description('Aggregated expenditure (EST to ETB)')
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Out of Service', $outOfServiceCount)
                ->description('Assets needing attention')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
            Stat::make('Operational Assets', $healthyCount)
                ->description('Maintained & healthy')
                ->icon('heroicon-o-check-badge')
                ->color('success'),
        ];
    }
}
