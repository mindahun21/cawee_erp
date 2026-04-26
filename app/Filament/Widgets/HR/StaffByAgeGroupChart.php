<?php

namespace App\Filament\Widgets\HR;

use App\Traits\BelongsToModuleWidget;

use App\Models\Employee;
use Filament\Widgets\ChartWidget;

class StaffByAgeGroupChart extends ChartWidget
{
    use BelongsToModuleWidget;

    protected ?string $heading = 'Staff Ratio by Age Group';

    protected static ?int $sort = 4;

    protected ?string $maxHeight = '320px';

    protected int | string | array $columnSpan = 1;

    protected function getData(): array
    {
        $employees = Employee::whereNull('date_resigned')
            ->whereNotNull('date_of_birth')
            ->get(['date_of_birth']);

        $groups = [
            'Under 25' => 0,
            '25 – 34'  => 0,
            '35 – 44'  => 0,
            '45 – 54'  => 0,
            '55+'      => 0,
        ];

        foreach ($employees as $e) {
            $age = $e->date_of_birth->age;
            if ($age < 25)      $groups['Under 25']++;
            elseif ($age < 35)  $groups['25 – 34']++;
            elseif ($age < 45)  $groups['35 – 44']++;
            elseif ($age < 55)  $groups['45 – 54']++;
            else                $groups['55+']++;
        }

        return [
            'datasets' => [[
                'data'            => array_values($groups),
                'backgroundColor' => [
                    'rgba(16, 185, 129, 0.85)',
                    'rgba(59, 130, 246, 0.85)',
                    'rgba(245, 158, 11, 0.85)',
                    'rgba(239, 68, 68, 0.85)',
                    'rgba(139, 92, 246, 0.85)',
                ],
                'borderWidth' => 2,
                'borderColor' => '#fff',
            ]],
            'labels' => array_keys($groups),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
