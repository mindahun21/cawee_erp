<?php

namespace App\Filament\Pages;

use App\Traits\BelongsToModulePage;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonationType;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use BackedEnum;
use UnitEnum;

class DonorFundraisingDashboard extends Page implements HasForms
{
    use BelongsToModulePage;

    use InteractsWithForms;

    public ?array $data = [];
    protected static ?string $slug = 'donor-fundraising-dashboard';
    protected static ?string $title = '';
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
        return [
            'default' => 1,
            'lg' => 2,
        ];
    }

    public function mount(): void
    {
        $this->form->fill([
            'filter_type' => 'this_month',
            'campaign_id' => null,
            'donor_id' => null,
            'donation_type_id' => null,
        ]);
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(4)
                    ->schema([
                        Select::make('filter_type')
                            ->label('Time Period')
                            ->options([
                                'today' => 'Today',
                                'this_week' => 'This Week',
                                'this_month' => 'This Month',
                                'this_year' => 'This Year',
                                'all_time' => 'All Time',
                                'custom' => 'Custom Range',
                            ])
                            ->default('this_month')
                            ->live()
                            ->afterStateUpdated(fn ($set) => $set('start_date', null) && $set('end_date', null)),
                        
                        Select::make('campaign_id')
                            ->label('Campaign')
                            ->options(Campaign::pluck('title', 'id'))
                            ->searchable()
                            ->placeholder('All Campaigns')
                            ->live(),

                        Select::make('donor_id')
                            ->label('Donor')
                            ->options(Donor::all()->pluck('full_name', 'id'))
                            ->searchable()
                            ->placeholder('All Donors')
                            ->live(),

                        Select::make('donation_type_id')
                            ->label('Category')
                            ->options(DonationType::pluck('name', 'id'))
                            ->placeholder('All Categories')
                            ->live(),
                    ]),
                
                Grid::make(2)
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->visible(fn ($get) => $get('filter_type') === 'custom')
                            ->required(fn ($get) => $get('filter_type') === 'custom')
                            ->live(),
                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->visible(fn ($get) => $get('filter_type') === 'custom')
                            ->required(fn ($get) => $get('filter_type') === 'custom')
                            ->live(),
                    ])->columnSpan(2),
            ])
            ->statePath('data');
    }

    public static function formatLargeNumber($number): string
    {
        if ($number >= 1000000) {
            return number_format($number / 1000000, 1) . 'M';
        }
        if ($number >= 1000) {
            return number_format($number / 1000, 1) . 'K';
        }
        return number_format($number);
    }

    protected function getViewData(): array
    {
        $filters = $this->form->getState();
        
        // 1. Date Range Logic
        $startDate = null;
        $endDate = null;

        switch ($filters['filter_type']) {
            case 'today':
                $startDate = now()->startOfDay();
                $endDate = now()->endOfDay();
                break;
            case 'this_week':
                $startDate = now()->startOfWeek();
                $endDate = now()->endOfWeek();
                break;
            case 'this_month':
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                break;
            case 'this_year':
                $startDate = now()->startOfYear();
                $endDate = now()->endOfYear();
                break;
            case 'custom':
                $startDate = $filters['start_date'] ? Carbon::parse($filters['start_date'])->startOfDay() : null;
                $endDate = $filters['end_date'] ? Carbon::parse($filters['end_date'])->endOfDay() : null;
                break;
        }

        // 2. Base Queries
        $donorQuery = Donor::query();
        $donationQuery = Donation::where('status', 'completed');

        // Apply Global Relationship Filters
        if ($filters['campaign_id']) {
            $donationQuery->where('campaign_id', $filters['campaign_id']);
            $donorQuery->whereHas('donations', fn($q) => $q->where('campaign_id', $filters['campaign_id']));
        }
        if ($filters['donor_id']) {
            $donationQuery->where('donor_id', $filters['donor_id']);
            $donorQuery->where('id', $filters['donor_id']);
        }
        if ($filters['donation_type_id']) {
            $donationQuery->where('donation_type_id', $filters['donation_type_id']);
            $donorQuery->whereHas('donations', fn($q) => $q->where('donation_type_id', $filters['donation_type_id']));
        }

        // Apply Date Filters
        if ($startDate && $endDate) {
            $donationQuery->whereBetween('donation_date', [$startDate, $endDate]);
            $donorQuery->whereHas('donations', fn($q) => $q->whereBetween('donation_date', [$startDate, $endDate]));
        }

        // 3. Lifetime (Unfiltered by date, but filtered by entity)
        $lifetimeDonationQuery = Donation::where('status', 'completed');
        $lifetimeDonorQuery = Donor::query();
        
        if ($filters['campaign_id']) {
            $lifetimeDonationQuery->where('campaign_id', $filters['campaign_id']);
            $lifetimeDonorQuery->whereHas('donations', fn($q) => $q->where('campaign_id', $filters['campaign_id']));
        }
        if ($filters['donor_id']) {
            $lifetimeDonationQuery->where('donor_id', $filters['donor_id']);
            $lifetimeDonorQuery->where('id', $filters['donor_id']);
        }
        if ($filters['donation_type_id']) {
            $lifetimeDonationQuery->where('donation_type_id', $filters['donation_type_id']);
            $lifetimeDonorQuery->whereHas('donations', fn($q) => $q->where('donation_type_id', $filters['donation_type_id']));
        }

        // 4. Calculations
        $totalDonorsAllTime = $lifetimeDonorQuery->count();
        $totalDonationsAllTime = $lifetimeDonationQuery->count();
        $totalRaisedAllTime = $lifetimeDonationQuery->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(base_amount, amount)'));
        $totalAvgDonationAllTime = $totalDonationsAllTime > 0 ? $totalRaisedAllTime / $totalDonationsAllTime : 0;

        $activeCampaigns = Campaign::where('status', 'active')->count();

        // 5. Filtered Period Metrics
        $donationsCount = $donationQuery->count();
        $raisedAmount = $donationQuery->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(base_amount, amount)'));
        $avgDonation = $donationsCount > 0 ? $raisedAmount / $donationsCount : 0;
        $uniqueDonors = $donorQuery->count();

        return [
            'totalDonors' => $totalDonorsAllTime,
            'totalDonationsAllTime' => $totalDonationsAllTime,
            'totalRaisedAllTime' => $totalRaisedAllTime,
            'totalAvgDonationAllTime' => $totalAvgDonationAllTime,
            'activeCampaigns' => $activeCampaigns,
            'donationsCount' => $donationsCount,
            'raisedAmount' => $raisedAmount,
            'avgDonation' => $avgDonation,
            'uniqueDonors' => $uniqueDonors,
            'filterLabel' => match($filters['filter_type']) {
                'today' => 'Today',
                'this_week' => 'This Week',
                'this_month' => 'This Month',
                'this_year' => 'This Year',
                'custom' => 'Selected Range',
                default => 'All Time',
            }
        ];
    }
}


