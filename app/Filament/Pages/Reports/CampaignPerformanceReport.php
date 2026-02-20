<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use App\Filament\Widgets\Reports\CampaignComparisonChart;
use App\Filament\Widgets\Reports\CampaignStatusDistributionChart;
use App\Services\ReportService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class CampaignPerformanceReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected string $view = 'filament.pages.reports.campaign-performance-report';
    
    protected static string | \UnitEnum | null $navigationGroup = 'Donor Fundraising / Reports';
    
    protected static ?string $title = 'Campaign Performance';

    public ?array $data = [];
    public array $campaigns = [];
    public array $stats = [];

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
                        'draft' => 'Draft',
                        'scheduled' => 'Scheduled',
                        'completed' => 'Completed',
                        'archived' => 'Archived',
                    ])
                    ->placeholder('All Status')
                    ->live(),
                DatePicker::make('date_from')
                    ->label('Start Date From')
                    ->live(),
                DatePicker::make('date_to')
                    ->label('Start Date To')
                    ->live(),
            ])
            ->columns(3)
            ->statePath('data');
    }

    protected function updateReport(): void
    {
        $service = app(ReportService::class);
        $this->campaigns = $service->getCampaignPerformanceData($this->data);
        
        // Calculate overview stats
        $totalRaised = array_sum(array_column($this->campaigns, 'total_raised'));
        $totalGoal = array_sum(array_column($this->campaigns, 'goal_amount'));
        $activeCount = count(array_filter($this->campaigns, fn($c) => $c['status'] === 'active'));
        $completedCount = count(array_filter($this->campaigns, fn($c) => $c['progress_percentage'] >= 100));
        
        $this->stats = [
            'total_campaigns' => count($this->campaigns),
            'active_count' => $activeCount,
            'total_raised' => $totalRaised,
            'avg_progress' => $totalGoal > 0 ? ($totalRaised / $totalGoal) * 100 : 0,
            'total_donors' => array_sum(array_column($this->campaigns, 'donor_count')),
            'completed_count' => $completedCount,
        ];

        $this->dispatch('updatePerformanceFilters', filters: $this->data);
    }

    public function updatedData(): void
    {
        $this->updateReport();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CampaignComparisonChart::class,
            CampaignStatusDistributionChart::class,
        ];
    }
}
