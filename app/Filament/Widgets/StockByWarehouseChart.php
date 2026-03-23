<?php

namespace App\Filament\Widgets;

use App\Models\Asset;
use App\Models\Warehouse;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class StockByWarehouseChart extends ChartWidget
{
    protected ?string $heading = 'Stock Distribution by Warehouse';

    protected ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = Warehouse::select('warehouses.name', DB::raw('SUM(item_warehouse.quantity) as total_qty'))
            ->join('item_warehouse', 'warehouses.id', '=', 'item_warehouse.warehouse_id')
            ->groupBy('warehouses.id', 'warehouses.name')
            ->pluck('total_qty', 'name')
            ->toArray();

        $colors = [
            'rgba(59, 130, 246, 0.85)', // blue
            'rgba(16, 185, 129, 0.85)', // green
            'rgba(245, 158, 11, 0.85)', // amber
            'rgba(139, 92, 246, 0.85)', // purple
            'rgba(244, 63, 94, 0.85)',  // rose
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Items',
                    'data' => array_values($data),
                    'backgroundColor' => array_slice(array_merge($colors, $colors), 0, count($data)),
                    'borderWidth' => 2,
                    'borderColor' => '#fff',
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
