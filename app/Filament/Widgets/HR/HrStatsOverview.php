<?php

namespace App\Filament\Widgets\HR;

use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class HrStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected string $view = 'filament-widgets::stats-overview-widget';

    protected function getStats(): array
    {
        $totalActive  = Employee::whereNull('date_resigned')->count();
        $newThisMonth = Employee::whereNull('date_resigned')
            ->whereMonth('date_of_employment', now()->month)
            ->whereYear('date_of_employment', now()->year)
            ->count();
        $resignedThisMonth = Employee::whereNotNull('date_resigned')
            ->whereMonth('date_resigned', now()->month)
            ->whereYear('date_resigned', now()->year)
            ->count();
        $birthdaysThisMonth = Employee::whereMonth('date_of_birth', now()->month)
            ->whereNull('date_resigned')
            ->count();

        return [
            Stat::make('Total Active Staff', $totalActive)
                ->description("{$newThisMonth} joined this month")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('New Hires This Month', $newThisMonth)
                ->description('vs ' . $resignedThisMonth . ' resigned')
                ->descriptionIcon($newThisMonth >= $resignedThisMonth ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($newThisMonth >= $resignedThisMonth ? 'success' : 'warning'),

            Stat::make('Resignations This Month', $resignedThisMonth)
                ->description('Staff turnover indicator')
                ->descriptionIcon('heroicon-m-arrow-left-end-on-rectangle')
                ->color($resignedThisMonth === 0 ? 'success' : 'danger'),

            Stat::make('Birthdays This Month 🎂', $birthdaysThisMonth)
                ->description('Employees with birthdays in ' . now()->format('F'))
                ->descriptionIcon('heroicon-m-cake')
                ->color('info'),
        ];
    }
}
