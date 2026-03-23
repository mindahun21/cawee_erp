<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\InventoryOverview;
use App\Filament\Widgets\StockByWarehouseChart;
use App\Filament\Widgets\StockValueChart;
use App\Filament\Widgets\MovementTrendChart;
use App\Filament\Widgets\LowStockWidget;
use App\Filament\Widgets\MaintenanceAlertsWidget;
use App\Filament\Widgets\AssetStatusChart;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\Maintenance;
use App\Models\Item;
use App\Models\ItemWarehouse;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\Cache;
use Filament\Pages\Page;
use UnitEnum;
use BackedEnum;

class InventoryDashboard extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static UnitEnum|string|null $navigationGroup = 'Inventory and Asset';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Inventory & Asset Dashboard';

    protected string $view = 'filament.pages.inventory-dashboard';

    public function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getWidgets(): array
    {
        return [
            AssetStatusChart::class,
            StockByWarehouseChart::class,
            StockValueChart::class,
            MovementTrendChart::class,
            LowStockWidget::class,
            MaintenanceAlertsWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 3;
    }

    protected function getViewData(): array
    {
        $metrics = Cache::remember('dashboard:inventory-view-data', now()->addMinutes(5), function (): array {
            return [
                'totalAssets' => Asset::count(),
                'totalItems' => Item::count(),
                'lowStockCount' => ItemWarehouse::whereRaw('quantity <= min_stock_value')
                    ->where('min_stock_value', '>', 0)
                    ->count(),
                'pendingMaintenance' => Maintenance::whereNotIn('status', ['Completed', 'Cancelled'])->count(),
                'activeAssignments' => AssetAssignment::whereNull('returned_date')->count(),
                'totalValue' => Asset::sum('purchase_cost'),
                'newAssetsMonth' => Asset::whereMonth('purchase_date', now()->month)
                    ->whereYear('purchase_date', now()->year)
                    ->count(),
                'movementsMonth' => InventoryMovement::whereMonth('date', now()->month)
                    ->whereYear('date', now()->year)
                    ->count(),
            ];
        });

        return $metrics;
    }
}
