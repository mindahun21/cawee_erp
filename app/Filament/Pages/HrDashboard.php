<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\HR\BirthdaysThisMonthWidget;
use App\Filament\Widgets\HR\HrStatsOverview;
use App\Filament\Widgets\HR\StaffByAgeGroupChart;
use App\Filament\Widgets\HR\StaffByDepartmentChart;
use App\Filament\Widgets\HR\StaffByJobPositionChart;
use App\Filament\Widgets\HR\StaffStatusByMonthChart;
use App\Models\Employee;
use App\Models\LeaveRequest;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class HrDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static string|UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationLabel = 'HR Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'HR Dashboard';

    protected string $view = 'filament.pages.hr-dashboard';

    public function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getWidgets(): array
    {
        return [
            HrStatsOverview::class,
            StaffByDepartmentChart::class,
            StaffByJobPositionChart::class,
            StaffByAgeGroupChart::class,
            StaffStatusByMonthChart::class,
            BirthdaysThisMonthWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 3;
    }

    protected function getViewData(): array
    {
        $totalActive       = Employee::whereNull('date_resigned')->count();
        $newThisMonth      = Employee::whereNull('date_resigned')
            ->whereMonth('date_of_employment', now()->month)
            ->whereYear('date_of_employment', now()->year)
            ->count();
        $resignedThisMonth = Employee::whereNotNull('date_resigned')
            ->whereMonth('date_resigned', now()->month)
            ->whereYear('date_resigned', now()->year)
            ->count();
        $birthdaysToday    = Employee::whereNull('date_resigned')
            ->whereMonth('date_of_birth', now()->month)
            ->whereDay('date_of_birth', now()->day)
            ->count();
        $birthdaysMonth    = Employee::whereNull('date_resigned')
            ->whereMonth('date_of_birth', now()->month)
            ->count();
        $onLeave           = LeaveRequest::where('approval_status', 'Approved')
            ->whereDate('start_date', '<=', today())
            ->whereDate('end_date', '>=', today())
            ->count();
        $pendingLeave      = LeaveRequest::where('approval_status', 'Pending')
            ->count();

        return [
            'totalActive'        => $totalActive,
            'newThisMonth'       => $newThisMonth,
            'resignedThisMonth'  => $resignedThisMonth,
            'birthdaysToday'     => $birthdaysToday,
            'birthdaysMonth'     => $birthdaysMonth,
            'onLeave'            => $onLeave,
            'pendingLeave'       => $pendingLeave,
            'netGrowth'          => $newThisMonth - $resignedThisMonth,
        ];
    }
}
