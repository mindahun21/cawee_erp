<?php

namespace App\Filament\Pages\Finance;

use App\Models\Currency;
use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryLine;
use App\Models\Finance\PaymentVoucher;
use App\Models\Finance\PaymentRequisition;
use App\Models\Finance\Budget;
use App\Models\Finance\GeneralLedger;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;
use App\Exports\GeneralReportExport;
use UnitEnum;

class FinanceReports extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.finance.finance-reports';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static string|UnitEnum|null   $navigationGroup = 'Finance';
    protected static ?string                $navigationLabel = 'Reports';
    protected static ?int                   $navigationSort  = 99;
    protected static ?string                $title           = 'Finance Reports';
    protected static ?string                $slug            = 'finance/reports';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user instanceof User && ($user->isFinanceOfficer() || $user->isFinanceManager() || $user->isSuperAdmin());
    }

    public function mount(): void
    {
        $this->mountInteractsWithTable();
    }

    // ── Table (drives the tabular reports) ────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->heading($this->getReportHeading())
            ->description($this->getReportDescription())
            ->columns($this->getReportColumns())
            ->defaultSort($this->getDefaultSortColumn(), 'desc');
    }

    protected function getTableQuery(): Builder
    {
        $report = request()->query('report', 'journal-entries');
        [$start, $end] = $this->resolveDateRange(request()->query('period', 'this_month'));
        $periodId = request()->query('period_id');
        $currency = request()->query('currency', 'ALL');

        return match ($report) {

            'journal-entries' => JournalEntry::query()
                ->with(['preparedBy', 'approvedBy', 'period', 'currency'])
                ->when($start && $end, fn (Builder $q) => $q->whereBetween('transaction_date', [$start, $end]))
                ->when($periodId, fn (Builder $q) => $q->where('accounting_period_id', $periodId))
                ->when($currency !== 'ALL', fn (Builder $q) => $q->whereHas('currency', fn($q2) => $q2->where('code', $currency))),

            'payment-vouchers' => PaymentVoucher::query()
                ->with(['preparedBy', 'approvedBy', 'currency'])
                ->when($start && $end, fn (Builder $q) => $q->whereBetween('payment_date', [$start, $end]))
                ->when($currency !== 'ALL', fn (Builder $q) => $q->whereHas('currency', fn($q2) => $q2->where('code', $currency))),

            'payment-requisitions' => PaymentRequisition::query()
                ->with(['preparedBy', 'approvedBy', 'currency'])
                ->when($start && $end, fn (Builder $q) => $q->whereBetween('requisition_date', [$start, $end]))
                ->when($currency !== 'ALL', fn (Builder $q) => $q->whereHas('currency', fn($q2) => $q2->where('code', $currency))),

            'trial-balance' => JournalEntryLine::query()
                ->with(['account', 'journalEntry.period'])
                ->selectRaw('
                    MIN(finance_journal_entry_lines.id) as id,
                    finance_journal_entry_lines.account_id,
                    SUM(finance_journal_entry_lines.debit)  as total_debit,
                    SUM(finance_journal_entry_lines.credit) as total_credit
                ')
                ->join('finance_journal_entries', 'finance_journal_entries.id', '=', 'finance_journal_entry_lines.journal_entry_id')
                ->where('finance_journal_entries.status', 'posted')
                ->when($periodId, fn (Builder $q) => $q->where('finance_journal_entries.accounting_period_id', $periodId))
                ->groupBy('finance_journal_entry_lines.account_id'),

            'budget-vs-actual' => Budget::query()
                ->with(['budgetType', 'approvedBy', 'costCenter', 'donor', 'project'])
                ->where('status', 'active')
                ->when(request()->query('fiscal_year'), fn (Builder $q) => $q->where('fiscal_year', request()->query('fiscal_year'))),

            'gl-ledger' => GeneralLedger::query()
                ->with(['account', 'period', 'journalEntryLine.journalEntry'])
                ->when($start && $end, fn (Builder $q) => $q->whereBetween('transaction_date', [$start, $end]))
                ->when($periodId, fn (Builder $q) => $q->where('period_id', $periodId))
                ->when($currency !== 'ALL', fn (Builder $q) => $q->whereHas('currency', fn($q2) => $q2->where('code', $currency))),

            default => JournalEntry::query()->whereRaw('1 = 0'),
        };
    }

    // ── Column sets per report ─────────────────────────────────────────

    protected function getReportColumns(): array
    {
        $report = request()->query('report', 'journal-entries');

        return match ($report) {

            'journal-entries' => [
                TextColumn::make('reference_number')->label('Reference')->badge()->color('primary')->fontFamily('mono')->searchable()->sortable(),
                TextColumn::make('transaction_date')->label('Date')->date('d M Y')->sortable(),
                TextColumn::make('period.name')->label('Period')->badge()->color('gray')->toggleable(),
                TextColumn::make('description')->label('Description')->limit(40)->searchable(),
                TextColumn::make('source')->label('Source')
                    ->formatStateUsing(fn ($s) => JournalEntry::sources()[$s] ?? $s)
                    ->badge()->color('gray'),
                TextColumn::make('status')->label('Status')
                    ->formatStateUsing(fn ($s) => JournalEntry::statuses()[$s] ?? $s)
                    ->badge()
                    ->color(fn ($s) => match($s) {
                        'posted' => 'success', 'approved' => 'info',
                        'pending_approval' => 'warning', 'draft' => 'gray',
                        'reversed' => 'danger', default => 'gray',
                    }),
                TextColumn::make('total_debit')
                    ->label('Total DR')
                    ->getStateUsing(fn (JournalEntry $r) => $r->lines()->sum('debit'))
                    ->formatStateUsing(fn ($s) => number_format((float)$s, 2))
                    ->fontFamily('mono')->color('success')->alignEnd()->sortable(false),
                TextColumn::make('preparedBy.name')->label('Prepared By')->toggleable(),
            ],

            'payment-vouchers' => [
                TextColumn::make('pv_number')->label('PV #')->badge()->color('primary')->fontFamily('mono')->searchable()->sortable(),
                TextColumn::make('payment_date')->label('Date')->date('d M Y')->sortable(),
                TextColumn::make('payee_name')->label('Payee')->searchable()->limit(28),
                TextColumn::make('payment_method')->label('Method')->badge()->color('gray'),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn ($s) => match($s) {
                        'posted' => 'success', 'approved' => 'info',
                        'pending_approval' => 'warning', 'draft' => 'gray', default => 'gray',
                    }),
                TextColumn::make('gross_amount')->label('Gross')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->sortable(),
                TextColumn::make('withholding_tax_amount')->label('WHT')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->toggleable(),
                TextColumn::make('net_amount')->label('Net Amount')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->weight('bold'),
                TextColumn::make('currency.code')->label('CCY')->badge()->color('gray')->toggleable(),
                TextColumn::make('preparedBy.name')->label('Prepared By')->toggleable(),
            ],

            'payment-requisitions' => [
                TextColumn::make('pr_number')->label('PR #')->badge()->color('primary')->fontFamily('mono')->searchable()->sortable(),
                TextColumn::make('requisition_date')->label('Date')->date('d M Y')->sortable(),
                TextColumn::make('payee_name')->label('Payee')->searchable()->limit(28),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn ($s) => match($s) {
                        'approved' => 'success', 'paid' => 'info',
                        'pending_approval' => 'warning', 'draft' => 'gray',
                        'rejected' => 'danger', default => 'gray',
                    }),
                TextColumn::make('total_amount')->label('Gross')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->sortable(),
                TextColumn::make('withholding_tax_amount')->label('WHT')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->toggleable(),
                TextColumn::make('net_payable')->label('Net Payable')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->weight('bold'),
                TextColumn::make('currency.code')->label('CCY')->badge()->color('gray')->toggleable(),
                TextColumn::make('preparedBy.name')->label('By')->toggleable(),
            ],

            'trial-balance' => [
                TextColumn::make('account.code')->label('Code')->fontFamily('mono')->badge()->color('gray')->sortable(false),
                TextColumn::make('account.name')->label('Account')->searchable()->weight('semibold'),
                TextColumn::make('account.accountType.name')->label('Type')->toggleable(),
                TextColumn::make('total_debit')->label('Total Debit (DR)')
                    ->formatStateUsing(fn ($s) => number_format((float)$s, 2))
                    ->fontFamily('mono')->color('success')->alignEnd()->sortable(false),
                TextColumn::make('total_credit')->label('Total Credit (CR)')
                    ->formatStateUsing(fn ($s) => number_format((float)$s, 2))
                    ->fontFamily('mono')->color('danger')->alignEnd()->sortable(false),
            ],

            'budget-vs-actual' => [
                TextColumn::make('budget_code')->label('Code')->badge()->color('primary')->fontFamily('mono')->sortable(),
                TextColumn::make('name')->label('Budget Name')->searchable()->limit(30),
                TextColumn::make('budgetType.name')->label('Type')->badge()->color('gray')->toggleable(),
                TextColumn::make('fiscal_year')->label('Year')->sortable(),
                TextColumn::make('total_budget_amount')->label('Budget')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->sortable(),
                TextColumn::make('actual_spent')->label('Actual Spent')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()
                    ->color(fn ($state, $record) => $record && (float)$state > (float)$record->total_budget_amount ? 'danger' : 'success'),
                TextColumn::make('utilization_pct')->label('Utilization %')
                    ->getStateUsing(fn ($record) => $record ? $record->utilizationPct() . '%' : '—')
                    ->badge()
                    ->color(fn ($state, $record) => !
                        $record ? 'gray' : ($record->utilizationPct() > 90 ? 'danger' : ($record->utilizationPct() > 70 ? 'warning' : 'success'))),
                TextColumn::make('status')->label('Status')->badge()
                    ->color(fn ($state) => match($state) {
                        'active' => 'success', 'approved' => 'warning',
                        'closed' => 'info', 'cancelled' => 'danger', default => 'gray',
                    }),
            ],

            'gl-ledger' => [
                TextColumn::make('transaction_date')->label('Date')->date('d M Y')->sortable(),
                TextColumn::make('journalEntryLine.journalEntry.reference_number')->label('JE Ref')->badge()->color('primary')->fontFamily('mono'),
                TextColumn::make('account.code')->label('Account Code')->fontFamily('mono')->badge()->color('gray'),
                TextColumn::make('account.name')->label('Account')->limit(35)->searchable(),
                TextColumn::make('debit')->label('Debit (DR)')
                    ->formatStateUsing(fn ($s) => (float)$s > 0 ? number_format((float)$s, 2) : '—')
                    ->fontFamily('mono')->color('success')->alignEnd(),
                TextColumn::make('credit')->label('Credit (CR)')
                    ->formatStateUsing(fn ($s) => (float)$s > 0 ? number_format((float)$s, 2) : '—')
                    ->fontFamily('mono')->color('danger')->alignEnd(),
                TextColumn::make('running_balance')->label('Running Balance')
                    ->formatStateUsing(fn ($s) => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->toggleable(),
                TextColumn::make('period.name')->label('Period')->badge()->color('gray')->toggleable(),
            ],

            default => [],
        };
    }

    // ── Heading / description helpers ──────────────────────────────────

    private function getReportHeading(): string
    {
        return match (request()->query('report', 'journal-entries')) {
            'journal-entries'      => 'Journal Entries Report',
            'payment-vouchers'     => 'Payment Vouchers Report',
            'payment-requisitions' => 'Payment Requisitions Report',
            'trial-balance'        => 'Trial Balance',
            'budget-vs-actual'     => 'Budget vs. Actual',
            'gl-ledger'            => 'General Ledger',
            default                => 'Finance Report',
        };
    }

    private function getReportDescription(): ?string
    {
        return match (request()->query('report', 'journal-entries')) {
            'journal-entries'      => 'All journal entries for the selected period.',
            'payment-vouchers'     => 'Payment vouchers by period and currency.',
            'payment-requisitions' => 'Payment requisitions by period and currency.',
            'trial-balance'        => 'Posted GL balances by account for the selected accounting period.',
            'budget-vs-actual'     => 'Active budgets showing budget amount vs actual spend.',
            'gl-ledger'            => 'Raw General Ledger postings for the selected period.',
            default                => null,
        };
    }

    private function getDefaultSortColumn(): string
    {
        return match (request()->query('report', 'journal-entries')) {
            'journal-entries'      => 'transaction_date',
            'payment-vouchers'     => 'payment_date',
            'payment-requisitions' => 'requisition_date',
            'gl-ledger'            => 'transaction_date',
            'budget-vs-actual'     => 'fiscal_year',
            default                => 'id',
        };
    }

    // ── Header actions (Export Excel / CSV) ───────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Action::make('export_excel')
                ->label('Export Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action(fn () => Excel::download(
                    $this->makeExport(),
                    'finance_' . request()->query('report', 'report') . '_' . now()->format('Ymd_His') . '.xlsx',
                )),

            Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn () => Excel::download(
                    $this->makeExport(),
                    'finance_' . request()->query('report', 'report') . '_' . now()->format('Ymd_His') . '.csv',
                    ExcelWriter::CSV,
                )),
        ];
    }

    private function makeExport(): GeneralReportExport
    {
        $columns = $this->getReportColumns();

        $headings = collect($columns)
            ->map(fn (TextColumn $col) => $col->getLabel() ?? $col->getName())
            ->values()->all();

        $rows = $this->getTableQuery()->get()->map(function ($record) use ($columns) {
            return collect($columns)->map(function (TextColumn $col) use ($record) {
                $state = $col->getState($record);
                if ($state instanceof \DateTimeInterface) return $state->format('Y-m-d');
                if (is_array($state)) return json_encode($state);
                return $state;
            })->all();
        })->all();

        return new GeneralReportExport($rows, $headings);
    }

    // ── Sub-navigation (left sidebar report selector) ─────────────────

    public function getSubNavigation(): array
    {
        $params  = ['period' => request()->query('period', 'this_month'), 'currency' => request()->query('currency', 'ALL')];
        $url     = fn (string $report) => url()->current() . '?' . http_build_query(array_merge($params, ['report' => $report]));

        return [
            NavigationItem::make('Journal Entries')
                ->icon('heroicon-o-book-open')
                ->url($url('journal-entries'))
                ->isActiveWhen(fn () => (request()->query('report', 'journal-entries')) === 'journal-entries'),

            NavigationItem::make('Payment Vouchers')
                ->icon('heroicon-o-receipt-percent')
                ->url($url('payment-vouchers'))
                ->isActiveWhen(fn () => request()->query('report') === 'payment-vouchers'),

            NavigationItem::make('Payment Requisitions')
                ->icon('heroicon-o-document-text')
                ->url($url('payment-requisitions'))
                ->isActiveWhen(fn () => request()->query('report') === 'payment-requisitions'),

            NavigationItem::make('Trial Balance')
                ->icon('heroicon-o-scale')
                ->url($url('trial-balance'))
                ->isActiveWhen(fn () => request()->query('report') === 'trial-balance'),

            NavigationItem::make('Budget vs. Actual')
                ->icon('heroicon-o-chart-bar')
                ->url($url('budget-vs-actual'))
                ->isActiveWhen(fn () => request()->query('report') === 'budget-vs-actual'),

            NavigationItem::make('General Ledger')
                ->icon('heroicon-o-archive-box')
                ->url($url('gl-ledger'))
                ->isActiveWhen(fn () => request()->query('report') === 'gl-ledger'),
        ];
    }

    // ── View data ─────────────────────────────────────────────────────

    protected function getViewData(): array
    {
        $period   = request()->query('period', 'this_month');
        $currency = request()->query('currency', 'ALL');
        $report   = request()->query('report', 'journal-entries');
        $periodId = request()->query('period_id');

        [$start, $end, $periodLabel] = $this->resolveDateRange($period);

        $currencyOptions = Currency::orderBy('code')->pluck('code', 'code')->toArray();
        $accountingPeriods = AccountingPeriod::orderByDesc('fiscal_year')
            ->orderByDesc('period_number')
            ->get()
            ->mapWithKeys(fn ($p) => [$p->id => $p->name])
            ->toArray();

        return compact(
            'period', 'currency', 'report', 'periodId',
            'periodLabel', 'currencyOptions', 'accountingPeriods'
        );
    }

    // ── Date range resolver ────────────────────────────────────────────

    private function resolveDateRange(string $period): array
    {
        $today = now()->startOfDay();

        return match ($period) {
            'last_month'   => [$today->copy()->subMonth()->startOfMonth(), $today->copy()->subMonth()->endOfMonth(), 'Last Month'],
            'this_year'    => [$today->copy()->startOfYear(), $today->copy()->endOfYear(), 'This Year'],
            'last_year'    => [$today->copy()->subYear()->startOfYear(), $today->copy()->subYear()->endOfYear(), 'Last Year'],
            'last_90_days' => [$today->copy()->subDays(89), $today->copy(), 'Last 90 Days'],
            'all_time'     => [null, null, 'All Time'],
            default        => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth(), 'This Month'],
        };
    }
}
