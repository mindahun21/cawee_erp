<?php

namespace App\Filament\Pages;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use BackedEnum;
use UnitEnum;

class DonorFundraisingDashboard extends Page
{
    protected static ?string $slug = 'donor-fundraising-dashboard';

    protected static string $routePath = 'donor-fundraising-dashboard';

    protected static string|UnitEnum|null $navigationGroup = 'Donor Fundraising';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -1;

    protected string $view = 'filament.pages.donor-fundraising-dashboard';

    public function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\DonorManagement\DonationTrendsChart::class,
            \App\Filament\Widgets\DonorManagement\DonationTypeDistributionChart::class,
            \App\Filament\Widgets\DonorManagement\CampaignPerformanceChart::class,
            \App\Filament\Widgets\DonorManagement\LatestDonationsWidget::class,
            \App\Filament\Widgets\DonorManagement\TopDonorsWidget::class,
        ];
    }

    public function getColumns(): int | array
    {
        return 3;
    }

    protected function getViewData(): array
    {
        $metrics = Cache::remember('dashboard:donor-view-data', now()->addMinutes(5), function (): array {
            $totalDonors = Donor::count();
            $activeCampaigns = Campaign::where('status', 'active')->count();

            $donationsTodayQuery = Donation::where('status', 'completed')
                ->whereDate('donation_date', today());
            $donationsToday = $donationsTodayQuery->count();
            $raisedToday = $donationsTodayQuery->sum('amount');

            $newDonorsThisMonth = Donor::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $donationsMonthQuery = Donation::where('status', 'completed')
                ->whereMonth('donation_date', now()->month)
                ->whereYear('donation_date', now()->year);
            $donationsThisMonth = $donationsMonthQuery->count();
            $raisedThisMonth = $donationsMonthQuery->sum('amount');
            $avgDonationThisMonth = $donationsThisMonth > 0 ? $raisedThisMonth / $donationsThisMonth : 0;

            return [
                'totalDonors' => $totalDonors,
                'activeCampaigns' => $activeCampaigns,
                'donationsToday' => $donationsToday,
                'raisedToday' => $raisedToday,
                'newDonorsThisMonth' => $newDonorsThisMonth,
                'donationsThisMonth' => $donationsThisMonth,
                'raisedThisMonth' => $raisedThisMonth,
                'avgDonationThisMonth' => $avgDonationThisMonth,
            ];
        });

        return $metrics;
    }
}


