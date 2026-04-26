<?php

namespace App\Filament\Widgets;

use App\Traits\BelongsToModuleWidget;

use Illuminate\Support\Facades\Cache;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryOverview extends StatsOverviewWidget
{
    use BelongsToModuleWidget;

    protected function getStats(): array
    {
        $metrics = Cache::remember('dashboard:inventory-overview', now()->addMinutes(5), fn (): array => [
            'total_assets' => \App\Models\Asset::count(),
            'total_investment' => \App\Models\Asset::sum('purchase_cost'),
            'active_assignments' => \App\Models\AssetAssignment::whereNull('returned_date')->count(),
            'low_stock' => \App\Models\ItemWarehouse::whereRaw('quantity <= min_stock_value')->count(),
            'pending_maintenance' => \App\Models\Maintenance::whereNotIn('status', ['Completed', 'Cancelled'])->count(),
        ]);

        return [
            Stat::make('Total Asset Value', 'ETB ' . number_format($metrics['total_investment'], 2))
                ->description('Total purchase cost of all assets')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Low Stock Items', $metrics['low_stock'])
                ->description('Items below reorder level')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($metrics['low_stock'] > 0 ? 'warning' : 'success'),

            Stat::make('Ongoing Maintenance', $metrics['pending_maintenance'])
                ->description('Active repair tasks')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color($metrics['pending_maintenance'] > 0 ? 'danger' : 'success'),
        ];
    }
}
