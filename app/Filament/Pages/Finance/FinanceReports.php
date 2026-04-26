<?php

namespace App\Filament\Pages\Finance;

use App\Models\Currency;
use App\Models\Donor;
use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\AccountType;
use App\Models\Finance\BankAccount;
use App\Models\Finance\BankReconciliation;
use App\Models\Finance\ChartOfAccount;
use App\Models\Finance\JournalEntry;
use App\Models\Finance\JournalEntryLine;
use App\Models\Finance\PaymentVoucher;
use App\Models\Finance\PaymentRequisition;
use App\Models\Finance\Budget;
use App\Models\Finance\GeneralLedger;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
        $report = request()->query('report') ?? '';

        $actions = [];
        if ($report === 'bank-reconciliation') {
            $actions = [
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
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->button(),
            ];
        }

        return $table
            ->heading($this->getReportHeading())
            ->description($this->getReportDescription())
            ->columns($this->getReportColumns())
            ->recordActions($actions)
            ->defaultSort($this->getDefaultSortColumn(), 'desc');
    }

    protected function getTableQuery(): Builder
    {
        // On the index page (no report selected) return an empty result set
        $report = request()->query('report') ?? '';
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

            'bank-reconciliation' => BankReconciliation::query()
                ->with(['bankAccount', 'period', 'preparedBy'])
                ->when($start && $end, fn (Builder $q) => $q->whereBetween('statement_date', [$start, $end]))
                ->when($periodId, fn (Builder $q) => $q->where('accounting_period_id', $periodId)),

            // ── Income Statement ──────────────────────────────────────────
            'income-statement' => JournalEntryLine::query()
                ->selectRaw("
                    MIN(finance_journal_entry_lines.id)                        AS id,
                    finance_chart_of_accounts.id                               AS account_id,
                    finance_chart_of_accounts.code                             AS account_code,
                    finance_chart_of_accounts.name                             AS account_name,
                    finance_account_types.classification                       AS classification,
                    finance_account_types.name                                 AS type_name,
                    SUM(finance_journal_entry_lines.debit)                     AS total_debit,
                    SUM(finance_journal_entry_lines.credit)                    AS total_credit,
                    SUM(finance_journal_entry_lines.credit)
                      - SUM(finance_journal_entry_lines.debit)                 AS net_amount
                ")
                ->join('finance_journal_entries',
                    'finance_journal_entries.id', '=', 'finance_journal_entry_lines.journal_entry_id')
                ->join('finance_chart_of_accounts',
                    'finance_chart_of_accounts.id', '=', 'finance_journal_entry_lines.account_id')
                ->join('finance_account_types',
                    'finance_account_types.id', '=', 'finance_chart_of_accounts.account_type_id')
                ->where('finance_journal_entries.status', 'posted')
                ->whereIn('finance_account_types.classification', ['income', 'expense'])
                ->when($start && $end, fn (Builder $q) =>
                    $q->whereBetween('finance_journal_entries.transaction_date', [$start, $end]))
                ->when($periodId, fn (Builder $q) =>
                    $q->where('finance_journal_entries.accounting_period_id', $periodId))
                ->groupBy(
                    'finance_chart_of_accounts.id',
                    'finance_chart_of_accounts.code',
                    'finance_chart_of_accounts.name',
                    'finance_account_types.classification',
                    'finance_account_types.name'
                )
                ->orderByRaw("FIELD(finance_account_types.classification, 'income', 'expense')")
                ->orderBy('finance_chart_of_accounts.code'),

            // ── Balance Sheet ─────────────────────────────────────────────
            'balance-sheet' => JournalEntryLine::query()
                ->selectRaw("
                    MIN(finance_journal_entry_lines.id)                        AS id,
                    finance_chart_of_accounts.id                               AS account_id,
                    finance_chart_of_accounts.code                             AS account_code,
                    finance_chart_of_accounts.name                             AS account_name,
                    finance_account_types.classification                       AS classification,
                    finance_account_types.name                                 AS type_name,
                    finance_account_types.normal_balance                       AS normal_balance,
                    SUM(finance_journal_entry_lines.debit)                     AS total_debit,
                    SUM(finance_journal_entry_lines.credit)                    AS total_credit,
                    CASE finance_account_types.normal_balance
                        WHEN 'debit'  THEN SUM(finance_journal_entry_lines.debit) - SUM(finance_journal_entry_lines.credit)
                        WHEN 'credit' THEN SUM(finance_journal_entry_lines.credit) - SUM(finance_journal_entry_lines.debit)
                    END                                                        AS balance
                ")
                ->join('finance_journal_entries',
                    'finance_journal_entries.id', '=', 'finance_journal_entry_lines.journal_entry_id')
                ->join('finance_chart_of_accounts',
                    'finance_chart_of_accounts.id', '=', 'finance_journal_entry_lines.account_id')
                ->join('finance_account_types',
                    'finance_account_types.id', '=', 'finance_chart_of_accounts.account_type_id')
                ->where('finance_journal_entries.status', 'posted')
                ->whereIn('finance_account_types.classification', ['asset', 'liability', 'equity'])
                ->when($periodId, fn (Builder $q) =>
                    $q->where('finance_journal_entries.accounting_period_id', $periodId))
                ->groupBy(
                    'finance_chart_of_accounts.id',
                    'finance_chart_of_accounts.code',
                    'finance_chart_of_accounts.name',
                    'finance_account_types.classification',
                    'finance_account_types.name',
                    'finance_account_types.normal_balance'
                )
                ->orderByRaw("FIELD(finance_account_types.classification, 'asset', 'liability', 'equity')")
                ->orderBy('finance_chart_of_accounts.code'),

            // ── Donor Fund Summary ────────────────────────────────────────
            'donor-fund-summary' => JournalEntryLine::query()
                ->selectRaw("
                    MIN(finance_journal_entry_lines.id)                        AS id,
                    finance_journal_entry_lines.donor_id                       AS donor_id,
                    donors.organization_name                                   AS donor_name,
                    CONCAT(COALESCE(donors.first_name,''),' ',COALESCE(donors.last_name,'')) AS donor_person,
                    donors.donor_type                                          AS donor_type,
                    SUM(CASE WHEN finance_account_types.classification = 'income'
                        THEN finance_journal_entry_lines.credit ELSE 0 END)   AS total_received,
                    SUM(CASE WHEN finance_account_types.classification = 'expense'
                        THEN finance_journal_entry_lines.debit  ELSE 0 END)   AS total_spent,
                    SUM(CASE WHEN finance_account_types.classification = 'income'
                        THEN finance_journal_entry_lines.credit ELSE 0 END)
                    - SUM(CASE WHEN finance_account_types.classification = 'expense'
                        THEN finance_journal_entry_lines.debit  ELSE 0 END)   AS remaining_balance,
                    COUNT(DISTINCT finance_journal_entry_lines.journal_entry_id) AS transaction_count
                ")
                ->join('finance_journal_entries',
                    'finance_journal_entries.id', '=', 'finance_journal_entry_lines.journal_entry_id')
                ->join('finance_chart_of_accounts',
                    'finance_chart_of_accounts.id', '=', 'finance_journal_entry_lines.account_id')
                ->join('finance_account_types',
                    'finance_account_types.id', '=', 'finance_chart_of_accounts.account_type_id')
                ->leftJoin('donors', 'donors.id', '=', 'finance_journal_entry_lines.donor_id')
                ->where('finance_journal_entries.status', 'posted')
                ->whereNotNull('finance_journal_entry_lines.donor_id')
                ->when($start && $end, fn (Builder $q) =>
                    $q->whereBetween('finance_journal_entries.transaction_date', [$start, $end]))
                ->when($periodId, fn (Builder $q) =>
                    $q->where('finance_journal_entries.accounting_period_id', $periodId))
                ->groupBy(
                    'finance_journal_entry_lines.donor_id',
                    'donors.organization_name',
                    'donors.first_name',
                    'donors.last_name',
                    'donors.donor_type'
                )
                ->orderByRaw('total_received DESC'),

            // ── Project Financial Summary ─────────────────────────────────
            'project-summary' => JournalEntryLine::query()
                ->selectRaw("
                    MIN(finance_journal_entry_lines.id)                            AS id,
                    finance_journal_entry_lines.project_id                         AS project_id,
                    hr_projects.project_name                                       AS project_name,
                    hr_projects.project_code                                       AS project_code,
                    COALESCE(SUM(finance_budgets.total_budget_amount), 0)          AS budget_allocated,
                    SUM(CASE WHEN finance_account_types.classification = 'expense'
                        THEN finance_journal_entry_lines.debit ELSE 0 END)         AS actual_spent,
                    COALESCE(SUM(finance_budgets.total_budget_amount), 0)
                      - SUM(CASE WHEN finance_account_types.classification = 'expense'
                        THEN finance_journal_entry_lines.debit ELSE 0 END)         AS remaining,
                    COUNT(DISTINCT finance_journal_entry_lines.journal_entry_id)   AS transaction_count
                ")
                ->join('finance_journal_entries',
                    'finance_journal_entries.id', '=', 'finance_journal_entry_lines.journal_entry_id')
                ->join('finance_chart_of_accounts',
                    'finance_chart_of_accounts.id', '=', 'finance_journal_entry_lines.account_id')
                ->join('finance_account_types',
                    'finance_account_types.id', '=', 'finance_chart_of_accounts.account_type_id')
                ->leftJoin('hr_projects',
                    'hr_projects.id', '=', 'finance_journal_entry_lines.project_id')
                ->leftJoin('finance_budgets',
                    'finance_budgets.project_id', '=', 'finance_journal_entry_lines.project_id')
                ->where('finance_journal_entries.status', 'posted')
                ->whereNotNull('finance_journal_entry_lines.project_id')
                ->when($start && $end, fn (Builder $q) =>
                    $q->whereBetween('finance_journal_entries.transaction_date', [$start, $end]))
                ->when($periodId, fn (Builder $q) =>
                    $q->where('finance_journal_entries.accounting_period_id', $periodId))
                ->groupBy(
                    'finance_journal_entry_lines.project_id',
                    'hr_projects.project_name',
                    'hr_projects.project_code'
                )
                ->orderByRaw('actual_spent DESC'),

            // ── Budget Utilization Detail ─────────────────────────────────
            'budget-utilization' => \App\Models\Finance\Budget::query()
                ->selectRaw("
                    finance_budgets.id,
                    finance_budgets.name                                           AS budget_name,
                    finance_budgets.fiscal_year,
                    finance_budgets.total_budget_amount,
                    finance_budgets.actual_spent,
                    finance_budgets.status,
                    donors.organization_name                                       AS donor_name,
                    hr_projects.project_name,
                    ROUND(
                      CASE WHEN finance_budgets.total_budget_amount > 0
                        THEN (finance_budgets.actual_spent / finance_budgets.total_budget_amount) * 100
                        ELSE 0
                      END, 1)                                                      AS utilization_pct,
                    finance_budgets.total_budget_amount - finance_budgets.actual_spent AS variance
                ")
                ->leftJoin('donors', 'donors.id', '=', 'finance_budgets.donor_id')
                ->leftJoin('hr_projects', 'hr_projects.id', '=', 'finance_budgets.project_id')
                ->when($start && $end, fn (Builder $q) =>
                    $q->where('finance_budgets.fiscal_year', date('Y', strtotime((string)$start))))
                ->orderBy('finance_budgets.fiscal_year', 'desc')
                ->orderByRaw('utilization_pct DESC'),

            // ── Account Statement ─────────────────────────────────────────
            'account-statement' => \App\Models\Finance\GeneralLedger::query()
                ->with(['account', 'period', 'journalEntryLine.journalEntry'])
                ->when($start && $end, fn (Builder $q) =>
                    $q->whereBetween('transaction_date', [$start, $end]))
                ->when($periodId, fn (Builder $q) =>
                    $q->where('period_id', $periodId))
                ->orderBy('transaction_date')
                ->orderBy('id'),

            // ── Cash Flow Statement ───────────────────────────────────────
            'cash-flow' => JournalEntryLine::query()
                ->selectRaw("
                    MIN(finance_journal_entry_lines.id)                            AS id,
                    finance_chart_of_accounts.code                                 AS account_code,
                    finance_chart_of_accounts.name                                 AS account_name,
                    finance_account_types.classification                           AS classification,
                    CASE
                        WHEN finance_chart_of_accounts.code LIKE 'D1%' THEN 'Operating'
                        WHEN finance_chart_of_accounts.code LIKE 'D2%' THEN 'Investing'
                        WHEN finance_chart_of_accounts.code LIKE 'D3%' THEN 'Financing'
                        ELSE 'Operating'
                    END                                                            AS flow_type,
                    SUM(finance_journal_entry_lines.debit)                         AS total_outflow,
                    SUM(finance_journal_entry_lines.credit)                        AS total_inflow,
                    SUM(finance_journal_entry_lines.credit)
                      - SUM(finance_journal_entry_lines.debit)                     AS net_flow
                ")
                ->join('finance_journal_entries',
                    'finance_journal_entries.id', '=', 'finance_journal_entry_lines.journal_entry_id')
                ->join('finance_chart_of_accounts',
                    'finance_chart_of_accounts.id', '=', 'finance_journal_entry_lines.account_id')
                ->join('finance_account_types',
                    'finance_account_types.id', '=', 'finance_chart_of_accounts.account_type_id')
                ->where('finance_journal_entries.status', 'posted')
                ->whereIn('finance_account_types.classification', ['asset', 'income', 'expense'])
                ->when($start && $end, fn (Builder $q) =>
                    $q->whereBetween('finance_journal_entries.transaction_date', [$start, $end]))
                ->when($periodId, fn (Builder $q) =>
                    $q->where('finance_journal_entries.accounting_period_id', $periodId))
                ->groupBy(
                    'finance_chart_of_accounts.code',
                    'finance_chart_of_accounts.name',
                    'finance_account_types.classification'
                )
                ->orderByRaw("FIELD(CASE WHEN finance_chart_of_accounts.code LIKE 'D1%' THEN 'Operating'
                    WHEN finance_chart_of_accounts.code LIKE 'D2%' THEN 'Investing'
                    WHEN finance_chart_of_accounts.code LIKE 'D3%' THEN 'Financing'
                    ELSE 'Operating' END, 'Operating','Investing','Financing')")
                ->orderBy('finance_chart_of_accounts.code'),

            // ── Aged Payables ─────────────────────────────────────────────
            'aged-payables' => \App\Models\Finance\PaymentVoucher::query()
                ->selectRaw("
                    finance_payment_vouchers.id,
                    finance_payment_vouchers.pv_number,
                    finance_payment_vouchers.payment_date,
                    finance_payment_vouchers.payee_name,
                    finance_payment_vouchers.payee_tin,
                    finance_payment_vouchers.gross_amount,
                    finance_payment_vouchers.net_amount,
                    finance_payment_vouchers.status,
                    DATEDIFF(NOW(), finance_payment_vouchers.payment_date)         AS age_days,
                    CASE
                        WHEN DATEDIFF(NOW(), finance_payment_vouchers.payment_date) <= 30  THEN '0-30 days'
                        WHEN DATEDIFF(NOW(), finance_payment_vouchers.payment_date) <= 60  THEN '31-60 days'
                        WHEN DATEDIFF(NOW(), finance_payment_vouchers.payment_date) <= 90  THEN '61-90 days'
                        ELSE '90+ days'
                    END                                                            AS age_bucket
                ")
                ->whereIn('status', ['approved', 'pending_approval'])
                ->when($start && $end, fn (Builder $q) =>
                    $q->whereBetween('payment_date', [$start, $end]))
                ->orderByRaw('age_days DESC'),

            // ── WHT / Tax Report ──────────────────────────────────────────
            'wht-report' => \App\Models\Finance\PaymentVoucher::query()
                ->selectRaw("
                    MIN(finance_payment_vouchers.id)                               AS id,
                    finance_payment_vouchers.payee_name,
                    finance_payment_vouchers.payee_tin,
                    finance_payment_vouchers.payee_type,
                    COUNT(finance_payment_vouchers.id)                             AS payment_count,
                    SUM(finance_payment_vouchers.gross_amount)                     AS total_gross,
                    SUM(finance_payment_vouchers.withholding_tax_amount)           AS total_wht,
                    SUM(finance_payment_vouchers.net_amount)                       AS total_net,
                    MAX(finance_payment_vouchers.withholding_tax_rate) * 100       AS wht_rate_pct
                ")
                ->where('status', 'posted')
                ->where('withholding_tax_amount', '>', 0)
                ->when($start && $end, fn (Builder $q) =>
                    $q->whereBetween('payment_date', [$start, $end]))
                ->groupBy(
                    'finance_payment_vouchers.payee_name',
                    'finance_payment_vouchers.payee_tin',
                    'finance_payment_vouchers.payee_type'
                )
                ->orderByRaw('total_wht DESC'),

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

            'bank-reconciliation' => [
                TextColumn::make('reference')->label('Reference')->badge()->color('primary')->fontFamily('mono')->searchable()->sortable()->copyable(),
                TextColumn::make('bankAccount.account_name')->label('Bank Account')->searchable()->weight('semibold')
                    ->description(fn (BankReconciliation $r): string => (string)($r->bankAccount?->bank_name . ' — ' . $r->bankAccount?->account_number)),
                TextColumn::make('period.name')->label('Period')->badge()->color('gray')->sortable(),
                TextColumn::make('statement_date')->label('Statement Date')->date('d M Y')->sortable(),
                TextColumn::make('statement_balance')->label('Stmt Balance')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->color('info'),
                TextColumn::make('gl_balance')->label('GL Balance')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->color('gray'),
                TextColumn::make('outstanding_deposits')->label('+ Deposits In Transit')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->color('success')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('outstanding_cheques')->label('− Outstanding Cheques')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->color('warning')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('adjusted_bank_balance')->label('Adjusted Balance')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->weight('semibold')->toggleable(),
                TextColumn::make('difference')->label('Difference')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->badge()
                    // @phpstan-ignore-next-line
                    ->color(fn (BankReconciliation $r): string => abs((float)$r->difference) < 0.01 ? 'success' : 'danger')->sortable(),
                TextColumn::make('status')->label('Status')->badge()
                    ->formatStateUsing(fn ($s): string => BankReconciliation::statuses()[$s] ?? ucfirst((string)$s))
                    // @phpstan-ignore-next-line
                    ->color(fn ($s): string => match($s) {
                        'reconciled' => 'success', 'in_progress' => 'warning',
                        'locked' => 'info', default => 'gray',
                    }),
                TextColumn::make('preparedBy.name')->label('Prepared By')->toggleable(isToggledHiddenByDefault: true),
            ],


            'income-statement' => [
                TextColumn::make('classification')
                    ->label('Category')
                    ->badge()
                    ->formatStateUsing(fn ($s): string => match($s) {
                        'income'  => 'Revenue / Income',
                        'expense' => 'Expenditure',
                        default   => ucfirst((string)$s),
                    })
                    // @phpstan-ignore-next-line
                    ->color(fn ($s): string => $s === 'income' ? 'success' : 'danger')
                    ->sortable(false),
                TextColumn::make('account_code')->label('Code')->fontFamily('mono')->badge()->color('gray')->sortable(false),
                TextColumn::make('account_name')->label('Account Name')->searchable()->weight('semibold')->sortable(false),
                TextColumn::make('type_name')->label('Type')->toggleable(isToggledHiddenByDefault: true)->sortable(false),
                TextColumn::make('total_debit')
                    ->label('Total Debit (DR)')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->color('danger')->sortable(false)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_credit')
                    ->label('Total Credit (CR)')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->color('success')->sortable(false)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('net_amount')
                    ->label('Net Amount')
                    ->formatStateUsing(fn ($s): string => number_format(abs((float)$s), 2))
                    ->fontFamily('mono')->alignEnd()->weight('bold')
                    // @phpstan-ignore-next-line
                    ->color(fn ($state, $record): string =>
                        ($record?->classification === 'income')
                            ? ((float)$state >= 0 ? 'success' : 'warning')
                            : ((float)$state <= 0 ? 'danger' : 'warning')
                    )->sortable(false),
            ],

            'balance-sheet' => [
                TextColumn::make('classification')
                    ->label('Section')
                    ->badge()
                    ->formatStateUsing(fn ($s): string => match($s) {
                        'asset'     => '▲ Assets',
                        'liability' => '▼ Liabilities',
                        'equity'    => '◆ Equity / Net Assets',
                        default     => ucfirst((string)$s),
                    })
                    // @phpstan-ignore-next-line
                    ->color(fn ($s): string => match($s) {
                        'asset'     => 'success',
                        'liability' => 'danger',
                        'equity'    => 'info',
                        default     => 'gray',
                    })->sortable(false),
                TextColumn::make('account_code')->label('Code')->fontFamily('mono')->badge()->color('gray')->sortable(false),
                TextColumn::make('account_name')->label('Account Name')->searchable()->weight('semibold')->sortable(false),
                TextColumn::make('total_debit')
                    ->label('Total Debit')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->color('gray')->sortable(false)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_credit')
                    ->label('Total Credit')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->color('gray')->sortable(false)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('balance')
                    ->label('Balance')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->weight('bold')
                    // @phpstan-ignore-next-line
                    ->color(fn ($state, $record): string => match($record?->classification) {
                        'asset'     => 'success',
                        'liability' => 'danger',
                        'equity'    => 'info',
                        default     => 'gray',
                    })->sortable(false),
            ],

            'donor-fund-summary' => [
                TextColumn::make('donor_name')
                    ->label('Donor')
                    ->searchable()
                    ->weight('semibold')
                    ->formatStateUsing(fn ($state, $record): string =>
                        trim((string)(($record?->donor_name ?: trim((string)$record?->donor_person)) ?: '— Unknown —'))
                    )->sortable(false),
                TextColumn::make('donor_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($s): string => ucfirst((string)$s))
                    // @phpstan-ignore-next-line
                    ->color(fn ($s): string => match($s) {
                        'foundation' => 'info',
                        'corporate'  => 'primary',
                        'individual' => 'gray',
                        default      => 'gray',
                    })->sortable(false),
                TextColumn::make('total_received')
                    ->label('Grants Received')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->color('success')->weight('semibold')->sortable(false),
                TextColumn::make('total_spent')
                    ->label('Total Expenditure')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->color('danger')->sortable(false),
                TextColumn::make('remaining_balance')
                    ->label('Remaining Balance')
                    ->formatStateUsing(fn ($s): string => number_format(abs((float)$s), 2))
                    ->fontFamily('mono')->alignEnd()->weight('bold')
                    // @phpstan-ignore-next-line
                    ->color(fn ($state): string => (float)$state >= 0 ? 'success' : 'danger')
                    ->sortable(false),
                TextColumn::make('transaction_count')
                    ->label('Transactions')
                    ->alignCenter()->badge()->color('gray')->sortable(false),
            ],


            'project-summary' => [
                TextColumn::make('project_code')->label('Code')->fontFamily('mono')->badge()->color('primary')->sortable(false),
                TextColumn::make('project_name')->label('Project')->searchable()->weight('semibold')->sortable(false),
                TextColumn::make('budget_allocated')->label('Budget Allocated')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->color('info')->sortable(false),
                TextColumn::make('actual_spent')->label('Actual Spent')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->color('danger')->weight('semibold')->sortable(false),
                TextColumn::make('remaining')->label('Remaining')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->weight('bold')
                    // @phpstan-ignore-next-line
                    ->color(fn ($state): string => (float)$state >= 0 ? 'success' : 'danger')
                    ->sortable(false),
                TextColumn::make('utilization_pct')->label('Utilization %')
                    ->getStateUsing(fn ($record): string => $record && (float)$record->budget_allocated > 0
                        ? round(((float)$record->actual_spent / (float)$record->budget_allocated) * 100, 1) . '%'
                        : '—')
                    ->badge()
                    // @phpstan-ignore-next-line
                    ->color(fn ($state, $record): string => !str_contains((string)$state, '%') ? 'gray' :
                        ((float)$record->actual_spent / max(0.01, (float)$record->budget_allocated) > 0.9 ? 'danger' :
                        ((float)$record->actual_spent / max(0.01, (float)$record->budget_allocated) > 0.7 ? 'warning' : 'success')))
                    ->sortable(false),
                TextColumn::make('transaction_count')->label('Transactions')->alignCenter()->badge()->color('gray')->sortable(false),
            ],

            'budget-utilization' => [
                TextColumn::make('fiscal_year')->label('Year')->badge()->color('gray')->sortable(),
                TextColumn::make('budget_name')->label('Budget')->searchable()->weight('semibold')->sortable(),
                TextColumn::make('donor_name')->label('Donor')->limit(25)->toggleable()->sortable(false),
                TextColumn::make('project_name')->label('Project')->limit(25)->toggleable()->sortable(false),
                TextColumn::make('total_budget_amount')->label('Budget')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->color('info')->sortable(),
                TextColumn::make('actual_spent')->label('Actual Spent')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->color('danger')->sortable(),
                TextColumn::make('variance')->label('Variance')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->weight('bold')
                    // @phpstan-ignore-next-line
                    ->color(fn ($state): string => (float)$state >= 0 ? 'success' : 'danger')
                    ->sortable(false),
                TextColumn::make('utilization_pct')->label('Utilization %')
                    ->formatStateUsing(fn ($s): string => $s . '%')
                    ->badge()
                    // @phpstan-ignore-next-line
                    ->color(fn ($state): string => (float)$state > 90 ? 'danger' : ((float)$state > 70 ? 'warning' : 'success'))
                    ->sortable(false),
                TextColumn::make('status')->label('Status')->badge()
                    // @phpstan-ignore-next-line
                    ->color(fn ($s): string => match($s) {
                        'active' => 'success', 'approved' => 'info',
                        'closed' => 'gray', 'cancelled' => 'danger', default => 'gray',
                    }),
            ],

            'account-statement' => [
                TextColumn::make('transaction_date')->label('Date')->date('d M Y')->sortable(),
                TextColumn::make('journalEntryLine.journalEntry.reference_number')
                    ->label('JE Ref')->badge()->color('primary')->fontFamily('mono'),
                TextColumn::make('account.code')->label('Account')->fontFamily('mono')->badge()->color('gray'),
                TextColumn::make('account.name')->label('Account Name')->limit(30)->searchable(),
                TextColumn::make('debit')->label('Debit (DR)')
                    ->formatStateUsing(fn ($s): string => (float)$s > 0 ? number_format((float)$s, 2) : '—')
                    ->fontFamily('mono')->color('danger')->alignEnd(),
                TextColumn::make('credit')->label('Credit (CR)')
                    ->formatStateUsing(fn ($s): string => (float)$s > 0 ? number_format((float)$s, 2) : '—')
                    ->fontFamily('mono')->color('success')->alignEnd(),
                TextColumn::make('running_balance')->label('Running Balance')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->weight('semibold'),
                TextColumn::make('period.name')->label('Period')->badge()->color('gray')->toggleable(),
            ],

            'cash-flow' => [
                TextColumn::make('flow_type')->label('Activity')
                    ->badge()
                    // @phpstan-ignore-next-line
                    ->color(fn ($s): string => match($s) {
                        'Operating'  => 'primary',
                        'Investing'  => 'info',
                        'Financing'  => 'warning',
                        default      => 'gray',
                    })->sortable(false),
                TextColumn::make('account_code')->label('Code')->fontFamily('mono')->badge()->color('gray')->sortable(false),
                TextColumn::make('account_name')->label('Account')->searchable()->weight('semibold')->sortable(false),
                TextColumn::make('total_inflow')->label('Cash Inflow (CR)')
                    ->formatStateUsing(fn ($s): string => (float)$s > 0 ? number_format((float)$s, 2) : '—')
                    ->fontFamily('mono')->alignEnd()->color('success')->sortable(false),
                TextColumn::make('total_outflow')->label('Cash Outflow (DR)')
                    ->formatStateUsing(fn ($s): string => (float)$s > 0 ? number_format((float)$s, 2) : '—')
                    ->fontFamily('mono')->alignEnd()->color('danger')->sortable(false),
                TextColumn::make('net_flow')->label('Net Cash Flow')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->weight('bold')
                    // @phpstan-ignore-next-line
                    ->color(fn ($state): string => (float)$state >= 0 ? 'success' : 'danger')
                    ->sortable(false),
            ],

            'aged-payables' => [
                TextColumn::make('age_bucket')->label('Age Bucket')
                    ->badge()
                    // @phpstan-ignore-next-line
                    ->color(fn ($s): string => match($s) {
                        '0-30 days'  => 'success',
                        '31-60 days' => 'warning',
                        '61-90 days' => 'danger',
                        '90+ days'   => 'danger',
                        default      => 'gray',
                    })->sortable(false),
                TextColumn::make('pv_number')->label('PV #')->badge()->color('primary')->fontFamily('mono')->searchable(),
                TextColumn::make('payment_date')->label('Date')->date('d M Y')->sortable(),
                TextColumn::make('payee_name')->label('Payee')->searchable()->weight('semibold')->limit(30),
                TextColumn::make('payee_tin')->label('TIN')->fontFamily('mono')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('age_days')->label('Days Outstanding')
                    ->formatStateUsing(fn ($s): string => number_format((int)$s) . ' days')
                    ->badge()
                    // @phpstan-ignore-next-line
                    ->color(fn ($state): string => (int)$state <= 30 ? 'success' : ((int)$state <= 60 ? 'warning' : 'danger'))
                    ->sortable(),
                TextColumn::make('gross_amount')->label('Gross')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->color('gray'),
                TextColumn::make('net_amount')->label('Net Payable')->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->weight('bold')->color('danger'),
                TextColumn::make('status')->label('Status')->badge()
                    // @phpstan-ignore-next-line
                    ->color(fn ($s): string => match($s) {
                        'approved' => 'success', 'pending_approval' => 'warning', default => 'gray',
                    }),
            ],

            'wht-report' => [
                TextColumn::make('payee_name')->label('Payee / Vendor')->searchable()->weight('semibold')->sortable(false),
                TextColumn::make('payee_tin')->label('TIN')->fontFamily('mono')->badge()->color('gray')->sortable(false),
                TextColumn::make('payee_type')->label('Type')->badge()
                    // @phpstan-ignore-next-line
                    ->color(fn ($s): string => match($s) {
                        'supplier' => 'primary', 'employee' => 'info', default => 'gray',
                    })->formatStateUsing(fn ($s): string => ucfirst((string)$s))->sortable(false),
                TextColumn::make('payment_count')->label('Payments')->alignCenter()->badge()->color('gray')->sortable(false),
                TextColumn::make('total_gross')->label('Gross Amount')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->color('gray')->sortable(false),
                TextColumn::make('wht_rate_pct')->label('WHT Rate')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2) . '%')
                    ->badge()->color('warning')->sortable(false),
                TextColumn::make('total_wht')->label('WHT Withheld')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->color('danger')->weight('bold')->sortable(false),
                TextColumn::make('total_net')->label('Net Paid')
                    ->formatStateUsing(fn ($s): string => number_format((float)$s, 2))
                    ->fontFamily('mono')->alignEnd()->color('success')->sortable(false),
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
            'bank-reconciliation'  => 'Bank Reconciliation Report',
            'income-statement'     => 'Income Statement (P&L)',
            'balance-sheet'        => 'Balance Sheet',
            'donor-fund-summary'   => 'Donor Fund Summary',
            'project-summary'      => 'Project Financial Summary',
            'budget-utilization'   => 'Budget Utilization Detail',
            'account-statement'    => 'Account Statement',
            'cash-flow'            => 'Cash Flow Statement',
            'aged-payables'        => 'Aged Payables Report',
            'wht-report'           => 'Withholding Tax (WHT) Report',
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
            'bank-reconciliation'  => 'Bank statement reconciliations with GL balance comparisons.',
            'income-statement'     => 'Revenue and expenditure by account from posted JEs — showing net surplus or deficit.',
            'balance-sheet'        => 'Assets, Liabilities and Equity balances from all posted journal entries to date.',
            'donor-fund-summary'   => 'Per-donor breakdown of grants received, total expenditure, and remaining fund balance.',
            'project-summary'      => 'Budget vs actual spending per project with utilization percentage.',
            'budget-utilization'   => 'Detailed budget lines with variance and utilization flags — identifies over/under-budget items.',
            'account-statement'    => 'Full transaction history for GL accounts with running balance.',
            'cash-flow'            => 'Cash inflows and outflows grouped by Operating, Investing and Financing activities.',
            'aged-payables'        => 'Outstanding approved payables grouped into 0-30, 31-60, 61-90 and 90+ day buckets.',
            'wht-report'           => 'Withholding tax summary per vendor — total gross, WHT withheld, and net paid for compliance reporting.',
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
            'bank-reconciliation'  => 'statement_date',
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

            NavigationItem::make('Bank Reconciliation')
                ->icon('heroicon-o-scale')
                ->url($url('bank-reconciliation'))
                ->isActiveWhen(fn () => request()->query('report') === 'bank-reconciliation'),

            NavigationItem::make('Income Statement')
                ->icon('heroicon-o-arrow-trending-up')
                ->url($url('income-statement'))
                ->isActiveWhen(fn () => request()->query('report') === 'income-statement'),

            NavigationItem::make('Balance Sheet')
                ->icon('heroicon-o-building-office-2')
                ->url($url('balance-sheet'))
                ->isActiveWhen(fn () => request()->query('report') === 'balance-sheet'),

            NavigationItem::make('Donor Fund Summary')
                ->icon('heroicon-o-heart')
                ->url($url('donor-fund-summary'))
                ->isActiveWhen(fn () => request()->query('report') === 'donor-fund-summary'),

            NavigationItem::make('Project Summary')
                ->icon('heroicon-o-briefcase')
                ->url($url('project-summary'))
                ->isActiveWhen(fn () => request()->query('report') === 'project-summary'),

            NavigationItem::make('Budget Utilization')
                ->icon('heroicon-o-chart-bar')
                ->url($url('budget-utilization'))
                ->isActiveWhen(fn () => request()->query('report') === 'budget-utilization'),

            NavigationItem::make('Account Statement')
                ->icon('heroicon-o-document-text')
                ->url($url('account-statement'))
                ->isActiveWhen(fn () => request()->query('report') === 'account-statement'),

            NavigationItem::make('Cash Flow')
                ->icon('heroicon-o-banknotes')
                ->url($url('cash-flow'))
                ->isActiveWhen(fn () => request()->query('report') === 'cash-flow'),

            NavigationItem::make('Aged Payables')
                ->icon('heroicon-o-clock')
                ->url($url('aged-payables'))
                ->isActiveWhen(fn () => request()->query('report') === 'aged-payables'),

            NavigationItem::make('WHT / Tax Report')
                ->icon('heroicon-o-receipt-refund')
                ->url($url('wht-report'))
                ->isActiveWhen(fn () => request()->query('report') === 'wht-report'),
        ];
    }

    // ── View data ─────────────────────────────────────────────────────

    protected function getViewData(): array
    {
        $period            = request()->query('period', 'this_month');
        $currency          = request()->query('currency', 'ALL');
        $report            = request()->query('report');   // null on index page
        $periodId          = request()->query('period_id');
        $dateFilterType    = request()->query('date_filter_type', 'period');
        $accountingMethod  = request()->query('accounting_method', 'accrual');

        [$start, $end, $periodLabel] = $this->resolveDateRange($period);

        $currencyOptions = Currency::orderBy('code')->pluck('code', 'code')->toArray();
        $accountingPeriods = AccountingPeriod::orderByDesc('fiscal_year')
            ->orderByDesc('period_number')
            ->get()
            ->mapWithKeys(fn ($p) => [$p->id => $p->name])
            ->toArray();

        return compact(
            'period', 'currency', 'report', 'periodId',
            'periodLabel', 'currencyOptions', 'accountingPeriods',
            'dateFilterType', 'accountingMethod'
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
