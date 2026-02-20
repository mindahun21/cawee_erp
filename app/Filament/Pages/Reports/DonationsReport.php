<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use App\Services\ReportService;
use App\Models\Campaign;
use App\Models\DonationType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class DonationsReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';

    protected string $view = 'filament.pages.reports.donations-report';
    
    protected static string | \UnitEnum | null $navigationGroup = 'Donor Fundraising / Reports';
    
    protected static ?string $title = 'Donations Report';

    public ?array $data = [];
    public array $donations = [];
    public array $summary = [];

    public function mount(): void
    {
        $this->form->fill();
        $this->updateReport();
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                DatePicker::make('date_from')->label('From'),
                DatePicker::make('date_to')->label('To'),
                Select::make('campaign_id')
                    ->label('Campaign')
                    ->options(Campaign::pluck('title', 'id'))
                    ->placeholder('All Campaigns'),
                Select::make('donation_type_id')
                    ->label('Type')
                    ->options(DonationType::pluck('name', 'id'))
                    ->placeholder('All Types'),
                Select::make('donor_type')
                    ->label('Donor Type')
                    ->options([
                        'individual' => 'Individual',
                        'organization' => 'Organization',
                    ])
                    ->placeholder('All Donors'),
                Select::make('is_recurring')
                    ->label('Frequency')
                    ->options([
                        '0' => 'One-time',
                        '1' => 'Recurring',
                    ])
                    ->placeholder('Any'),
                TextInput::make('min_amount')->label('Min Amount')->numeric(),
                TextInput::make('max_amount')->label('Max Amount')->numeric(),
            ])
            ->columns(4)
            ->statePath('data');
    }

    public function updateReport(): void
    {
        $service = app(ReportService::class);
        $this->donations = $service->getDonationReportData($this->data);
        
        $totalAmount = array_sum(array_column($this->donations, 'amount'));
        $count = count($this->donations);

        $this->summary = [
            'count' => $count,
            'total_amount' => $totalAmount,
            'avg_amount' => $count > 0 ? $totalAmount / $count : 0,
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
