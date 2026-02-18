<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use App\Filament\Widgets\Reports\RevenueForecastChart;

class RecurringForecastReport extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected string $view = 'filament.pages.reports.recurring-forecast-report';
    
    protected static string | \UnitEnum | null $navigationGroup = 'Donor Fundraising / Reports';
    
    protected static ?string $title = 'Recurring Donation Forecast';

    protected function getHeaderWidgets(): array
    {
        return [
            RevenueForecastChart::class,
        ];
    }
}
