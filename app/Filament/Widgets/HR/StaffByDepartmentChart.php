<?php

namespace App\Filament\Widgets\HR;

use App\Traits\BelongsToModuleWidget;

use App\Models\Employee;
use Filament\Widgets\ChartWidget;

class StaffByDepartmentChart extends ChartWidget
{
    use BelongsToModuleWidget;

    protected ?string $heading = 'Staff Ratio by Department';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '320px';

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $data = Employee::query()
            ->whereNull('date_resigned')
            ->whereNotNull('department_id')
            ->selectRaw('department_id, COUNT(*) as count')
            ->groupBy('department_id')
            ->with('department:id,name')
            ->get();

        $unassigned = Employee::whereNull('date_resigned')->whereNull('department_id')->count();

        $labels = $data->map(fn ($r) => $r->department?->name ?? 'Unknown')->toArray();
        $counts = $data->pluck('count')->toArray();

        if ($unassigned > 0) {
            $labels[] = 'Unassigned';
            $counts[] = $unassigned;
        }

        $colors = [
            'rgba(59, 130, 246, 0.85)',
            'rgba(16, 185, 129, 0.85)',
            'rgba(245, 158, 11, 0.85)',
            'rgba(239, 68, 68, 0.85)',
            'rgba(139, 92, 246, 0.85)',
            'rgba(236, 72, 153, 0.85)',
            'rgba(14, 165, 233, 0.85)',
            'rgba(168, 85, 247, 0.85)',
        ];

        return [
            'datasets' => [[
                'data'            => $counts,
                'backgroundColor' => array_slice(array_merge($colors, $colors), 0, count($counts)),
                'borderWidth'     => 2,
                'borderColor'     => '#fff',
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
