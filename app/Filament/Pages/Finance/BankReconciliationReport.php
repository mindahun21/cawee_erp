<?php

namespace App\Filament\Pages\Finance;

use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\BankAccount;
use App\Models\Finance\BankReconciliation;
use App\Models\Finance\BankReconciliationItem;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\GeneralReportExport;
use Maatwebsite\Excel\Excel as ExcelWriter;
use UnitEnum;

class BankReconciliationReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.finance.bank-reconciliation-report';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-scale';
    protected static string|UnitEnum|null   $navigationGroup = 'Finance';
    protected static ?string                $navigationLabel = 'Bank Reconciliation Report';
    protected static ?int                   $navigationSort  = 98;
    protected static ?string                $title           = 'Bank Reconciliation Report';
    protected static ?string                $slug            = 'finance/reports/bank-reconciliation';

    // Hidden from sidebar — report is accessible via Finance → Reports → Bank Reconciliation
    protected static bool $shouldRegisterNavigation = false;

    // ── State (filter properties) ─────────────────────────────────────

    public ?int    $bankAccountId      = null;
    public ?int    $accountingPeriodId = null;
    public ?string $status             = null;
    public ?string $fromDate           = null;
    public ?string $toDate             = null;

    // ── Summary (computed on load) ────────────────────────────────────

    public int   $totalReconciliations  = 0;
    public int   $reconciledCount       = 0;
    public int   $inProgressCount       = 0;
    public float $totalDifference       = 0.0;

    // ── Auth ──────────────────────────────────────────────────────────

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user instanceof User &&
               ($user->isFinanceOfficer() || $user->isFinanceManager() || $user->isSuperAdmin());
    }

    // ── Lifecycle ─────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->mountInteractsWithTable();
        $this->refreshSummary();
    }

    // ── Table ─────────────────────────────────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->heading('Bank Reconciliation Statements')
            ->description('A comprehensive view of all bank reconciliations, filtered by account, period, and status.')
            ->columns([
                TextColumn::make('reference')
                    ->label('Ref #')
                    ->badge()
                    ->color('primary')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('bankAccount.account_name')
                    ->label('Bank Account')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn (BankReconciliation $record): string =>
                        (string) ($record->bankAccount?->bank_name . ' — ' . $record->bankAccount?->account_number)
                    ),

                TextColumn::make('period.name')
                    ->label('Period')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('statement_date')
                    ->label('Statement Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('statement_balance')
                    ->label('Bank Statement')
                    ->numeric(decimalPlaces: 2)
                    ->fontFamily('mono')
                    ->alignEnd()
                    ->color('info'),

                TextColumn::make('gl_balance')
                    ->label('GL Balance')
                    ->numeric(decimalPlaces: 2)
                    ->fontFamily('mono')
                    ->alignEnd()
                    ->color('gray'),

                TextColumn::make('outstanding_deposits')
                    ->label('+ Deposits In Transit')
                    ->numeric(decimalPlaces: 2)
                    ->fontFamily('mono')
                    ->alignEnd()
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('outstanding_cheques')
                    ->label('− Outstanding Cheques')
                    ->numeric(decimalPlaces: 2)
                    ->fontFamily('mono')
                    ->alignEnd()
                    ->color('warning')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('adjusted_bank_balance')
                    ->label('Adjusted Bank Balance')
                    ->numeric(decimalPlaces: 2)
                    ->fontFamily('mono')
                    ->alignEnd()
                    ->weight('semibold')
                    ->toggleable(),

                TextColumn::make('difference')
                    ->label('Difference')
                    ->numeric(decimalPlaces: 2)
                    ->fontFamily('mono')
                    ->alignEnd()
                    ->badge()
                    // @phpstan-ignore-next-line (Filament 5 accepts closures here)
                    ->color(fn (BankReconciliation $record): string =>
                        abs((float) $record->difference) < 0.01 ? 'success' : 'danger'
                    )
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => BankReconciliation::statuses()[$state] ?? ucfirst((string) $state))
                    // @phpstan-ignore-next-line (Filament 5 accepts closures here)
                    ->color(fn ($state): string => match ($state) {
                        'reconciled'  => 'success',
                        'in_progress' => 'warning',
                        'locked'      => 'info',
                        default       => 'gray',
                    }),

                TextColumn::make('preparedBy.name')
                    ->label('Prepared By')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reconciled_at')
                    ->label('Reconciled At')
                    ->dateTime('d M Y H:i')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('bank_account_id')
                    ->label('Bank Account')
                    // @phpstan-ignore-next-line (Filament 5 accepts closures here)
                    ->options(fn () => BankAccount::where('is_active', true)
                        ->orderBy('account_name')
                        ->get()
                        ->mapWithKeys(fn ($b) => [(string) $b->id => "{$b->bank_name} — {$b->account_name}"])
                        ->toArray()
                    )
                    ->searchable(),

                SelectFilter::make('accounting_period_id')
                    ->label('Accounting Period')
                    // @phpstan-ignore-next-line (Filament 5 accepts closures here)
                    ->options(fn () => AccountingPeriod::orderByDesc('fiscal_year')
                        ->orderByDesc('period_number')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->searchable(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(BankReconciliation::statuses()),
            ])
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\Action::make('print_summary')
                        ->label('Print Summary')
                        ->icon('heroicon-o-document-text')
                        ->color('gray')
                        ->url(fn (BankReconciliation $record) => route('finance.bank-reconciliation.summary', $record))
                        ->openUrlInNewTab(),

                    \Filament\Actions\Action::make('print_detail')
                        ->label('Print Detail')
                        ->icon('heroicon-o-document-magnifying-glass')
                        ->color('gray')
                        ->url(fn (BankReconciliation $record) => route('finance.bank-reconciliation.detail', $record))
                        ->openUrlInNewTab(),
                ])
                ->label('Options')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->button(),
            ])
            ->defaultSort('statement_date', 'desc')
            ->striped()
            ->paginated(true)
            ->deferLoading();
    }

    protected function getTableQuery(): Builder
    {
        return BankReconciliation::query()
            ->with(['bankAccount', 'period', 'preparedBy', 'reviewedBy', 'items']);
    }

    // ── Summary refresh ───────────────────────────────────────────────

    private function refreshSummary(): void
    {
        $this->totalReconciliations = BankReconciliation::count();
        $this->reconciledCount      = BankReconciliation::query()->where('status', 'reconciled')->count();
        $this->inProgressCount      = BankReconciliation::query()->where('status', 'in_progress')->count();
        $this->totalDifference      = (float) BankReconciliation::sum('difference');
    }

    // ── Header Actions ────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action(function () {
                    $records = BankReconciliation::with(['bankAccount', 'period'])->get();

                    $headings = [
                        'Reference', 'Bank Account', 'Period', 'Statement Date',
                        'Bank Statement Balance', 'GL Balance',
                        'Deposits In Transit', 'Outstanding Cheques',
                        'Adjusted Bank Balance', 'Difference', 'Status',
                        'Reconciled At',
                    ];

                    $rows = $records->map(fn (BankReconciliation $r) => [
                        $r->reference,
                        "{$r->bankAccount?->bank_name} — {$r->bankAccount?->account_name}",
                        $r->period?->name,
                        $r->statement_date?->format('d M Y'),
                        number_format((float) $r->statement_balance, 2),
                        number_format((float) $r->gl_balance, 2),
                        number_format((float) $r->outstanding_deposits, 2),
                        number_format((float) $r->outstanding_cheques, 2),
                        number_format((float) $r->adjusted_bank_balance, 2),
                        number_format((float) $r->difference, 2),
                        BankReconciliation::statuses()[$r->status] ?? $r->status,
                        $r->reconciled_at?->format('d M Y H:i') ?? '—',
                    ])->toArray();

                    return Excel::download(
                        new GeneralReportExport($rows, $headings),
                        'bank_reconciliation_report_' . now()->format('Ymd_His') . '.xlsx'
                    );
                }),

            Action::make('export_pdf')
                ->label('Print / PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('finance.bank-reconciliation.pdf'))
                ->openUrlInNewTab()
                ->visible(fn () => false), // Enable when PDF route is configured
        ];
    }

    // ── View data ─────────────────────────────────────────────────────

    protected function getViewData(): array
    {
        // KPI summaries for view
        $totalReconciliations = BankReconciliation::count();
        $reconciledCount      = BankReconciliation::where('status', 'reconciled')->count();
        $inProgressCount      = BankReconciliation::where('status', 'in_progress')->count();
        $lockedCount          = BankReconciliation::where('status', 'locked')->count();

        // Most recent reconciliation per bank account
        $latestPerAccount = BankReconciliation::with(['bankAccount', 'period'])
            ->orderByDesc('statement_date')
            ->get()
            ->unique('bank_account_id')
            ->take(6);

        // Accounts with unreconciled difference
        $unreconciledAccounts = BankReconciliation::where('status', 'in_progress')
            ->with('bankAccount')
            ->orderByDesc('statement_date')
            ->get();

        return compact(
            'totalReconciliations',
            'reconciledCount',
            'inProgressCount',
            'lockedCount',
            'latestPerAccount',
            'unreconciledAccounts'
        );
    }
}
