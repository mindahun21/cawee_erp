<?php

namespace App\Filament\Widgets;

use App\Models\InventoryMovement;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MovementTrendChart extends ChartWidget
{
    protected ?string $heading = 'Inventory Movement Trends (Last 30 Days)';

    protected ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $days = collect(range(29, 0))->map(fn ($i) => now()->subDays($i)->format('Y-m-d'));

        $movements = InventoryMovement::where('date', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(date) as day'),
                DB::raw('SUM(CASE WHEN movement_type = "in" THEN quantity ELSE 0 END) as total_in'),
                DB::raw('SUM(CASE WHEN movement_type = "out" THEN quantity ELSE 0 END) as total_out')
            )
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $inData = $days->map(fn ($day) => $movements->get($day)?->total_in ?? 0);
        $outData = $days->map(fn ($day) => $movements->get($day)?->total_out ?? 0);

        return [
            'datasets' => [
                [
                    'label' => 'Stock In',
                    'data' => $inData->toArray(),
                    'borderColor' => 'rgba(16, 185, 129, 1)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Stock Out',
                    'data' => $outData->toArray(),
                    'borderColor' => 'rgba(239, 68, 68, 1)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $days->map(fn ($d) => Carbon::parse($d)->format('M d'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
