<?php

namespace App\Filament\Resources\Donations\Widgets;

use App\Models\Donation;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DonationChartWidget extends ChartWidget
{
    protected ?string $heading = 'Monthly Donation Trends';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Get last 12 months of data
        $data = Donation::where('status', 'completed')
            ->selectRaw("strftime('%Y-%m', donation_date) as month, SUM(amount) as total")
            ->where('donation_date', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $labels = [];
        $amounts = [];

        foreach ($data as $item) {
            $labels[] = date('M Y', strtotime($item->month . '-01'));
            $amounts[] = (float) $item->total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Donations',
                    'data' => $amounts,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
