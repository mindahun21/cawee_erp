<?php

namespace App\Filament\Pages;

use App\Exports\GeneralReportExport;
use App\Models\Currency;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Campaign;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use UnitEnum;
use Illuminate\Support\Carbon;

class DonorReports extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected string $view = 'filament.pages.donor-reports';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string|UnitEnum|null $navigationGroup = 'Donor Fundraising';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 20;

    protected static ?string $title = 'Donor Reports';

    protected static ?string $slug = 'donor-reports';

    public ?string $activeTab = 'summary';

    public ?array $filters = [
        'period' => 'this_month',
        'start_date' => null,
        'end_date' => null,
        'currency' => 'ALL',
        'campaign_id' => null,
    ];

    public function mount(): void
    {
        $this->mountInteractsWithTable();
        
        // Load query params if present
        $this->activeTab = request()->query('tab', 'summary');
        $this->filters['period'] = request()->query('period', 'this_month');
        $this->filters['start_date'] = request()->query('start_date');
        $this->filters['end_date'] = request()->query('end_date');
        $this->filters['currency'] = request()->query('currency', 'ALL');
        
        $this->form->fill($this->filters);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('period')
                                    ->label('Time Period')
                                    ->options([
                                        'this_month' => 'This Month',
                                        'last_month' => 'Last Month',
                                        'last_90_days' => 'Last 90 Days',
                                        'this_year' => 'This Year',
                                        'last_year' => 'Last Year',
                                        'custom' => 'Custom Range',
                                        'all_time' => 'All Time',
                                    ])
                                    ->default('this_month')
                                    ->live(),
                                DatePicker::make('start_date')
                                    ->label('From Date')
                                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('period') === 'custom')
                                    ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('period') === 'custom')
                                    ->live(),
                                DatePicker::make('end_date')
                                    ->label('To Date')
                                    ->visible(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('period') === 'custom')
                                    ->required(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('period') === 'custom')
                                    ->live(),
                                Select::make('currency')
                                    ->label('Currency')
                                    ->options(fn () => array_merge(['ALL' => 'All Currencies'], Currency::pluck('code', 'code')->toArray()))
                                    ->default('ALL')
                                    ->live(),
                                Select::make('campaign_id')
                                    ->label('Campaign')
                                    ->options(fn () => Campaign::pluck('title', 'id')->toArray())
                                    ->placeholder('All Campaigns')
                                    ->live()
                                    ->hidden(fn() => $this->activeTab === 'campaigns'),
                            ]),
                    ])
                    ->compact(),
            ])
            ->statePath('filters');
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns($this->getReportColumns())
            ->defaultSort($this->getReportDefaultSortColumn(), $this->getReportDefaultSortDirection())
            ->emptyStateHeading('No data found for this report')
            ->emptyStateDescription('Adjust your filters or try a different period.')
            ->paginated([10, 25, 50, 100]);
    }

    public function getTableRecordKey($record): string
    {
        return (string) ($record->id ?? spl_object_id($record));
    }

    protected function getTableQuery(): Builder
    {
        [$startDate, $endDate] = $this->resolveDateRange($this->filters['period']);
        $currency = $this->filters['currency'];
        $campaignId = $this->filters['campaign_id'] ?? null;
        $report = $this->activeTab;
        $driver = DB::getDriverName();

        if ($report === 'trends') {
            // Table is hidden in trends tab, but we must return a valid query
            return Donation::query()->whereRaw('1 = 0');
        }

        return match ($report) {
            'geography' => Donation::query()
                ->select(
                    DB::raw($driver === 'sqlite' 
                        ? "COALESCE(donors.country, 'Unknown') || '-' || COALESCE(donors.city, 'Unknown') as id" 
                        : "CONCAT(COALESCE(donors.country, 'Unknown'), '-', COALESCE(donors.city, 'Unknown')) as id"),
                    'donors.country', 
                    'donors.city', 
                    DB::raw('COUNT(donations.id) as donation_count'), 
                    DB::raw('SUM(donations.amount) as total_amount')
                )
                ->join('donors', 'donations.donor_id', '=', 'donors.id')
                ->when($currency !== 'ALL', fn (Builder $q) => $q->where('donations.currency_id', function ($query) use ($currency) {
                    $query->select('id')->from('currencies')->where('code', $currency)->limit(1);
                }))
                ->when($campaignId, fn (Builder $q) => $q->where('donations.campaign_id', $campaignId))
                ->when($startDate && $endDate, fn (Builder $q) => $q->whereBetween('donations.donation_date', [$startDate, $endDate]))
                ->groupBy('donors.country', 'donors.city'),

            'campaigns' => Donation::query()
                ->select(
                    'campaigns.id',
                    'campaigns.title as campaign_title', 
                    DB::raw('COUNT(donations.id) as donation_count'), 
                    DB::raw('SUM(donations.amount) as total_amount')
                )
                ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
                ->when($currency !== 'ALL', fn (Builder $q) => $q->where('donations.currency_id', function ($query) use ($currency) {
                    $query->select('id')->from('currencies')->where('code', $currency)->limit(1);
                }))
                ->when($startDate && $endDate, fn (Builder $q) => $q->whereBetween('donations.donation_date', [$startDate, $endDate]))
                ->groupBy('campaigns.id', 'campaigns.title'),

            default => Donation::query()
                ->with(['donor', 'campaign', 'donationType', 'currency'])
                ->when($currency !== 'ALL', fn (Builder $q) => $q->whereHas('currency', fn ($query) => $query->where('code', $currency)))
                ->when($campaignId, fn (Builder $q) => $q->where('campaign_id', $campaignId))
                ->when($startDate && $endDate, fn (Builder $q) => $q->whereBetween('donation_date', [$startDate, $endDate])),
        };
    }

    protected function getReportColumns(): array
    {
        return match ($this->activeTab) {
            'geography' => [
                TextColumn::make('country')->label('Country')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('city')->label('City')->searchable()->sortable(),
                TextColumn::make('donation_count')->label('Donations')->numeric()->alignEnd()->sortable(),
                TextColumn::make('total_amount')->label('Total Amount')->numeric(decimalPlaces: 2)->alignEnd()->sortable(),
            ],

            'campaigns' => [
                TextColumn::make('campaign_title')->label('Campaign')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('donation_count')->label('Donations')->numeric()->alignEnd()->sortable(),
                TextColumn::make('total_amount')->label('Raised Amount')->numeric(decimalPlaces: 2)->alignEnd()->sortable(),
            ],

            default => [
                TextColumn::make('donor.full_name')->label('Donor')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('donationType.name')->label('Type')->badge()->color('info')->sortable(),
                TextColumn::make('campaign.title')->label('Campaign')->searchable()->toggleable()->placeholder('—'),
                TextColumn::make('donation_date')->label('Date')->date()->sortable(),
                TextColumn::make('status')->label('Status')->badge()->color('success'),
                TextColumn::make('amount')->money(fn ($record) => $record->currency?->code ?? 'ETB')->alignEnd()->sortable(),
            ],
        };
    }

    protected function getReportDefaultSortColumn(): string
    {
        return match ($this->activeTab) {
            'geography' => 'total_amount',
            'campaigns' => 'total_amount',
            default => 'donation_date',
        };
    }

    protected function getReportDefaultSortDirection(): string
    {
        return 'desc';
    }

    public function export(string $format): mixed
    {
        $columns = $this->getReportColumns();
        $headings = collect($columns)->map(fn (TextColumn $c) => $c->getLabel() ?? $c->getName())->values()->all();
        $data = $this->getTableQuery()->get();

        $rows = $data->map(function ($record) use ($columns) {
            return collect($columns)->map(fn (TextColumn $c) => $c->getState($record))->all();
        })->all();

        $filename = "donor_report_{$this->activeTab}_" . now()->format('Ymd_His');

        return match ($format) {
            'excel' => Excel::download(new GeneralReportExport($rows, $headings), "{$filename}.xlsx"),
            'csv' => Excel::download(new GeneralReportExport($rows, $headings), "{$filename}.csv", ExcelWriter::CSV),
            'pdf' => Excel::download(new GeneralReportExport($rows, $headings), "{$filename}.pdf", ExcelWriter::DOMPDF),
            default => null,
        };
    }

    protected function getViewData(): array
    {
        [$startDate, $endDate, $periodLabel] = $this->resolveDateRange($this->filters['period']);
        
        $stats = [
            'total_amount' => Donation::whereBetween('donation_date', [$startDate ?? '1970-01-01', $endDate ?? '2099-12-31'])
                ->when($this->filters['currency'] !== 'ALL', fn($q) => $q->whereHas('currency', fn($c) => $c->where('code', $this->filters['currency'])))
                ->when($this->filters['campaign_id'], fn($q) => $q->where('campaign_id', $this->filters['campaign_id']))
                ->sum('amount'),
            'count' => Donation::whereBetween('donation_date', [$startDate ?? '1970-01-01', $endDate ?? '2099-12-31'])
                ->when($this->filters['currency'] !== 'ALL', fn($q) => $q->whereHas('currency', fn($c) => $c->where('code', $this->filters['currency'])))
                ->when($this->filters['campaign_id'], fn($q) => $q->where('campaign_id', $this->filters['campaign_id']))
                ->count(),
            'donors' => Donor::whereHas('donations', function($q) use ($startDate, $endDate) {
                    $q->whereBetween('donation_date', [$startDate ?? '1970-01-01', $endDate ?? '2099-12-31'])
                      ->when($this->filters['currency'] !== 'ALL', fn($sq) => $sq->whereHas('currency', fn($c) => $c->where('code', $this->filters['currency'])))
                      ->when($this->filters['campaign_id'], fn($sq) => $sq->where('campaign_id', $this->filters['campaign_id']));
                })->count(),
        ];

        return [
            'summary' => $stats,
            'periodLabel' => $periodLabel,
            'chartData' => $this->activeTab === 'trends' ? $this->getTrendsData($startDate, $endDate) : [],
        ];
    }

    private function getTrendsData($startDate, $endDate): array
    {
        $driver = DB::getDriverName();
        $dateGroup = $driver === 'sqlite' 
            ? 'strftime("%Y-%m", donation_date)' 
            : 'DATE_FORMAT(donation_date, "%Y-%m")';

        $query = Donation::query()
            ->select(DB::raw("$dateGroup as month"), DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('donation_date', [$startDate, $endDate]))
            ->groupBy('month')
            ->orderBy('month');

        $data = $query->get();

        return [
            'labels' => $data->pluck('month')->toArray(),
            'amounts' => $data->pluck('total')->map(fn($v) => (float)$v)->toArray(),
            'counts' => $data->pluck('count')->toArray(),
        ];
    }

    private function resolveDateRange(string $period): array
    {
        $today = now()->startOfDay();

        return match ($period) {
            'last_month' => [
                $today->copy()->subMonth()->startOfMonth(),
                $today->copy()->subMonth()->endOfMonth(),
                'Last Month',
            ],
            'this_year' => [
                $today->copy()->startOfYear(),
                $today->copy()->endOfYear(),
                'This Year',
            ],
            'last_year' => [
                $today->copy()->subYear()->startOfYear(),
                $today->copy()->subYear()->endOfYear(),
                'Last Year',
            ],
            'last_90_days' => [
                $today->copy()->subDays(89),
                $today->copy(),
                'Last 90 Days',
            ],
            'custom' => [
                $this->filters['start_date'] ? Carbon::parse($this->filters['start_date'])->startOfDay() : null,
                $this->filters['end_date'] ? Carbon::parse($this->filters['end_date'])->endOfDay() : null,
                'Custom Range',
            ],
            'all_time' => [null, null, 'All Time'],
            default => [
                $today->copy()->startOfMonth(),
                $today->copy()->endOfMonth(),
                'This Month',
            ],
        };
    }
}
