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
        $totalAssets = Asset::sum('quantity');
        
        // Calculate total value in ETB (estimation based on currency rates)
        // We use COALESCE to handle cases where currency or exchange_rate might be missing
        $totalValueEtb = Asset::query()
            ->leftJoin('currencies', 'assets.currency_id', '=', 'currencies.id')
            ->selectRaw('SUM(assets.purchase_cost * assets.quantity * COALESCE(currencies.exchange_rate, 1)) as total')
            ->value('total') ?? 0;

        $availableStatusId = AssetStatus::where('name', 'Available')->value('id');
        $maintenanceStatusId = AssetStatus::where('name', 'Maintenance')->value('id');
        
        $availableAssets = Asset::where('asset_status_id', $availableStatusId)->sum('quantity');
        $maintenanceAssets = Asset::where('asset_status_id', $maintenanceStatusId)->sum('quantity');

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
