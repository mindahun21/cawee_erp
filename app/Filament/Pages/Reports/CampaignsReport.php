<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use App\Services\ReportService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class CampaignsReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-megaphone';

    protected string $view = 'filament.pages.reports.campaigns-report';
    
    protected static string | \UnitEnum | null $navigationGroup = 'Donor Fundraising / Reports';
    
    protected static ?string $title = 'Campaigns Report';

    public ?array $data = [];
    public array $campaigns = [];
    public array $summary = [];
    public array $insights = [];

    public function mount(): void
    {
        $this->form->fill();
        $this->updateReport();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'upcoming' => 'Upcoming',
                        'completed' => 'Completed',
                        'paused' => 'Paused',
                        'draft' => 'Draft',
                    ])
                    ->placeholder('All Status'),
                DatePicker::make('date_from')->label('Start Date From'),
                DatePicker::make('date_to')->label('End Date To'),
                TextInput::make('min_goal')->label('Min Goal')->numeric(),
                TextInput::make('max_goal')->label('Max Goal')->numeric(),
                TextInput::make('min_raised')->label('Min Raised')->numeric(),
                TextInput::make('min_donors')->label('Min Donors')->numeric(),
            ])
            ->columns(4)
            ->statePath('data');
    }

    public function updateReport(): void
    {
        $service = app(ReportService::class);
        $this->campaigns = $service->getCampaignPerformanceData($this->data);
        
        // Calculate Metrics
        $totalRaised = array_sum(array_column($this->campaigns, 'total_raised'));
        $totalGoal = array_sum(array_column($this->campaigns, 'goal_amount'));
        $totalDonations = array_sum(array_column($this->campaigns, 'donation_count'));
        $totalDonors = array_sum(array_column($this->campaigns, 'donor_count'));

        $this->summary = [
            'total_campaigns' => count($this->campaigns),
            'active_count' => count(array_filter($this->campaigns, fn($c) => $c['status'] === 'active')),
            'total_raised' => $totalRaised,
            'overall_progress' => $totalGoal > 0 ? ($totalRaised / $totalGoal) * 100 : 0,
            'total_donors' => $totalDonors,
            'total_donations' => $totalDonations,
            'avg_donation' => $totalDonations > 0 ? $totalRaised / $totalDonations : 0,
        ];

        // Insights
        $this->insights = [
            'top_campaign' => collect($this->campaigns)->sortByDesc('total_raised')->first(),
            'most_engaging' => collect($this->campaigns)->sortByDesc('donor_count')->first(),
            'best_performing' => collect($this->campaigns)->sortByDesc('progress_percentage')->first(),
        ];
    }

    public function submitFilters(): void
    {
        $this->updateReport();
    }

    public function resetFilters(): void
    {
        $this->form->fill();
        $this->updateReport();
    }
}
