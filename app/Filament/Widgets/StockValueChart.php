<?php

namespace App\Filament\Widgets;

use App\Models\Asset;
use App\Models\AssetCategory;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class StockValueChart extends ChartWidget
{
    protected ?string $heading = 'Stock Value by Category';

    protected ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $data = AssetCategory::select('asset_categories.name', DB::raw('SUM(assets.purchase_cost) as total_value'))
            ->join('asset_models', 'asset_categories.id', '=', 'asset_models.asset_category_id')
            ->join('assets', 'asset_models.id', '=', 'assets.asset_model_id')
            ->groupBy('asset_categories.id', 'asset_categories.name')
            ->orderByDesc('total_value')
            ->pluck('total_value', 'name')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Total Value (ETB)',
                    'data' => array_values($data),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.85)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 1,
                    'borderRadius' => 6,
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
