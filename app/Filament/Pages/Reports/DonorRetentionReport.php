<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use App\Filament\Widgets\Reports\RetentionChart;
use App\Filament\Widgets\Reports\ChurnChart;

class DonorRetentionReport extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-path';

    protected string $view = 'filament.pages.reports.donor-retention-report';
    
    protected static string | \UnitEnum | null $navigationGroup = 'Donor Fundraising / Reports';
    
    protected static ?string $title = 'Donor Retention & Churn';

    protected function getHeaderWidgets(): array
    {
        return [
            RetentionChart::class,
            ChurnChart::class,
        ];
    }
}
