<?php

namespace App\Filament\Widgets\HR;

use App\Traits\BelongsToModuleWidget;

use App\Models\Employee;
use Filament\Widgets\ChartWidget;

class StaffByJobPositionChart extends ChartWidget
{
    use BelongsToModuleWidget;

    protected ?string $heading = 'Staff Ratio by Job Position';

    protected static ?int $sort = 3;

    protected ?string $maxHeight = '320px';

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $data = Employee::query()
            ->whereNull('date_resigned')
            ->whereNotNull('job_position_id')
            ->selectRaw('job_position_id, COUNT(*) as count')
            ->groupBy('job_position_id')
            ->with('jobPosition:id,title')
            ->get()
            ->sortByDesc('count')
            ->take(10);

        $labels = $data->map(fn ($r) => $r->jobPosition?->title ?? 'Unknown')->values()->toArray();
        $counts = $data->pluck('count')->values()->toArray();

        return [
            'datasets' => [[
                'label'           => 'Employees',
                'data'            => $counts,
                'backgroundColor' => 'rgba(59, 130, 246, 0.8)',
                'borderColor'     => 'rgba(59, 130, 246, 1)',
                'borderWidth'     => 1,
                'borderRadius'    => 6,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
