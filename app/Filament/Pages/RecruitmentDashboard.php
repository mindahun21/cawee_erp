<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\RecruitmentStats;
use UnitEnum;

class RecruitmentDashboard extends Page
{
    protected string $view = 'filament.pages.recruitment-dashboard';
    protected static string|UnitEnum|null $navigationGroup = 'Recruitment';

    protected function getWidgets(): array
    {
        return [
            // RecruitmentStats::class,
        ];
    }
}
