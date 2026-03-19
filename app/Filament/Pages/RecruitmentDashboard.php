<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard;
use BackedEnum;
use UnitEnum;

class RecruitmentDashboard extends Dashboard
{
    protected static string|UnitEnum|null $navigationGroup = 'Recruitment';
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $title = 'Recruitment Dashboard';
    protected static ?int $navigationSort = 1;
    
    protected static string $routePath = 'recruitment-dashboard'; 

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\Recruitment\RecruitmentStatsWidget::class,
            \App\Filament\Widgets\Recruitment\RecruitmentPlansChart::class,
            \App\Filament\Widgets\Recruitment\RecruitmentSkillsChart::class,
        ];
    }
}
