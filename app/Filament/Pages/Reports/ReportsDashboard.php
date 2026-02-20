<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use App\Filament\Widgets\Reports\ReportsKPIOverview;
use App\Filament\Widgets\Reports\MonthlyTrendChart;
use App\Filament\Widgets\Reports\DashboardSegmentationChart;
use App\Services\ReportService;

class ReportsDashboard extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected string $view = 'filament.pages.reports.reports-dashboard';
    
    protected static string | \UnitEnum | null $navigationGroup = 'Donor Fundraising / Reports';
    
    protected static ?string $title = 'Dashboard & Analytics';

    public array $recentDonations = [];
    public array $topCampaigns = [];

    public function mount(): void
    {
        $service = app(ReportService::class);
        $this->recentDonations = $service->getRecentDonations(5);
        $this->topCampaigns = $service->getTopCampaigns(5);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ReportsKPIOverview::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            MonthlyTrendChart::class,
            DashboardSegmentationChart::class,
        ];
    }
}
