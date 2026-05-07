<?php

namespace App\Filament\Resources\Assets\Widgets;

use App\Models\Asset;
use App\Models\AssetStatus;
use App\Models\Currency;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class AssetStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $assets = Asset::all();
        $totalAssets = $assets->sum('quantity');
        
        $totalValueEtb = $assets->sum(function ($asset) {
            $rate = $asset->currency?->exchange_rate ?? 1;
            return $asset->purchase_cost * $asset->quantity * $rate;
        });

        $availableStatusId = AssetStatus::where('name', 'Available')->value('id');
        $maintenanceStatusId = AssetStatus::where('name', 'Maintenance')->value('id');
        
        $availableAssets = $assets->where('asset_status_id', $availableStatusId)->sum('quantity');
        $maintenanceAssets = $assets->where('asset_status_id', $maintenanceStatusId)->sum('quantity');

        return [
            Stat::make('Total Assets', number_format($totalAssets))
                ->description('Total items in inventory')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary'),

            Stat::make('Total Valuation (EST)', 'ETB ' . number_format($totalValueEtb, 2))
                ->description('Estimated value in ETB')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Available', number_format($availableAssets))
                ->description('Ready for assignment')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),

            Stat::make('In Maintenance', number_format($maintenanceAssets))
                ->description('Currently being serviced')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('warning'),
        ];
    }
}
