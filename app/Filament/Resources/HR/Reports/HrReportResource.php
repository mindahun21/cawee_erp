<?php

namespace App\Filament\Resources\HR\Reports;

use App\Models\Employee;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use App\Traits\BelongsToModule;

class HrReportResource extends Resource
{
    use BelongsToModule;
    protected static ?string $model = Employee::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Human Resources';

    protected static ?string $navigationLabel = 'HR Reports';

    protected static ?int $navigationSort = 20;

    protected static ?string $slug = 'hr-reports';

    public static function getPages(): array
    {
        return [
            'index'          => Pages\StaffListReport::route('/'),
            'layoffs'        => Pages\LayoffsReport::route('/layoffs'),
            'salary'         => Pages\SalaryReport::route('/salary'),
            'qualifications' => Pages\QualificationsReport::route('/qualifications'),
            'seniority'      => Pages\SeniorityReport::route('/seniority'),
        ];
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }
    public static function canView($record): bool { return false; }

    /**
     * Sub-nav links shared across all report pages.
     */
    public static function getReportNavLinks(): array
    {
        return [
            ['label' => '👥 Staff List',           'route' => 'filament.admin.resources.hr-reports.index'],
            ['label' => '📤 Laying Off Staffs',    'route' => 'filament.admin.resources.hr-reports.layoffs'],
            ['label' => '💰 Salary Changes',       'route' => 'filament.admin.resources.hr-reports.salary'],
            ['label' => '🎓 Qualifications',       'route' => 'filament.admin.resources.hr-reports.qualifications'],
            ['label' => '📈 Staff by Seniority',   'route' => 'filament.admin.resources.hr-reports.seniority'],
        ];
    }
}
