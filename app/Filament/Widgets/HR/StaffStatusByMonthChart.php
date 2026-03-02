<?php

namespace App\Filament\Widgets\HR;

use App\Models\Employee;
use Filament\Widgets\ChartWidget;

class StaffStatusByMonthChart extends ChartWidget
{
    protected ?string $heading = 'Staff Changes by Month (This Year)';

    protected static ?int $sort = 5;

    protected ?string $maxHeight = '300px';

    protected int | string | array $columnSpan = 2;

    protected function getData(): array
    {
        $months   = [];
        $hired    = [];
        $resigned = [];

        for ($m = 1; $m <= now()->month; $m++) {
            $months[] = now()->month($m)->format('M');

            $hired[] = Employee::whereMonth('date_of_employment', $m)
                ->whereYear('date_of_employment', now()->year)
                ->count();

            $resigned[] = Employee::whereMonth('date_resigned', $m)
                ->whereYear('date_resigned', now()->year)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label'           => 'New Hires',
                    'data'            => $hired,
                    'borderColor'     => 'rgba(16, 185, 129, 1)',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
                [
                    'label'           => 'Resignations',
                    'data'            => $resigned,
                    'borderColor'     => 'rgba(239, 68, 68, 1)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
