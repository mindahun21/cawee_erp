<?php

namespace App\Filament\Pages\Finance;

use App\Models\Finance\AccountingPeriod;
use App\Models\Finance\BankAccount;
use App\Models\Finance\BankReconciliation;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action as TableAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class Reconcile extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.pages.finance.reconcile';

    protected static string|BackedEnum|null $navigationIcon  = 'heroicon-o-adjustments-horizontal';
    protected static string|UnitEnum|null   $navigationGroup = 'Finance';
    protected static ?string                $navigationLabel = 'Reconcile';
    protected static ?int                   $navigationSort  = 5;
    protected static ?string                $title           = 'Bank Reconciliation';
    protected static ?string                $slug            = 'finance/reconcile';

    // ── Form state ────────────────────────────────────────────────────

    public ?int    $bank_account_id     = null;
    public ?float  $ending_balance      = null;
    public ?string $ending_date         = null;
    // Computed on-the-fly from the GeneralLedger — never entered by hand
    public ?float  $computed_gl_balance = null;

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
    }

    // ── Table — full reconciliation list ──────────────────────────────

    public function table(Table $table): Table
    {
        return $table
            ->heading('All Reconciliations')
            ->description('Full history of bank reconciliation statements. Use Resume to continue an in-progress reconciliation, or View to inspect a completed one.')
            ->query(
                BankReconciliation::query()
                    ->with(['bankAccount', 'period', 'preparedBy'])
                    ->latest('statement_date')
            )
            ->columns([
                TextColumn::make('reference')
                    ->label('Reference')
                    ->badge()->color('primary')->fontFamily('mono')
                    ->searchable()->sortable()->copyable(),

                TextColumn::make('bankAccount.account_name')
                    ->label('Bank Account')
                    ->searchable()->weight('semibold')
                    ->description(fn (BankReconciliation $r): string =>
                        (string) ($r->bankAccount?->bank_name . ' — ' . $r->bankAccount?->account_number)
                    ),

                TextColumn::make('period.name')
                    ->label('Period')
                    ->badge()->color('gray')->sortable(),

                TextColumn::make('statement_date')
                    ->label('Statement Date')
                    ->date('d M Y')->sortable(),

                TextColumn::make('statement_balance')
                    ->label('Bank Balance')
                    ->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->color('info'),

                TextColumn::make('gl_balance')
                    ->label('GL Balance')
                    ->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->color('gray'),

                TextColumn::make('difference')
                    ->label('Difference')
                    ->numeric(decimalPlaces: 2)->fontFamily('mono')->alignEnd()->badge()
                    // @phpstan-ignore-next-line
                    ->color(fn (BankReconciliation $r): string =>
                        abs((float) $r->difference) < 0.01 ? 'success' : 'danger'
                    )->sortable(),

                TextColumn::make('status')
                    ->label('Status')->badge()
                    ->formatStateUsing(fn ($s): string => BankReconciliation::statuses()[$s] ?? ucfirst((string) $s))
                    // @phpstan-ignore-next-line
                    ->color(fn ($s): string => match ($s) {
                        'reconciled'  => 'success',
                        'in_progress' => 'warning',
                        'locked'      => 'info',
                        default       => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('bank_account_id')
                    ->label('Bank Account')
                    // @phpstan-ignore-next-line
                    ->options(fn () => BankAccount::where('is_active', true)
                        ->orderBy('account_name')
                        ->get()
                        ->mapWithKeys(fn ($b) => [(string) $b->id => "{$b->bank_name} — {$b->account_name}"])
                        ->toArray()
                    )->searchable(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(BankReconciliation::statuses()),
            ])
            ->recordActions([
                TableAction::make('resume')
                    ->label('Resume')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->button()
                    ->visible(fn ($record) => $record->status === 'in_progress')
                    ->url(fn ($record) => route(
                        'filament.admin.resources.finance.bank.reconciliations.edit', $record
                    )),

                TableAction::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->visible(fn ($record) => $record->status !== 'in_progress')
                    ->url(fn ($record) => route(
                        'filament.admin.resources.finance.bank.reconciliations.view', $record
                    )),

                TableAction::make('mark_reconciled')
                    ->label('Mark Reconciled')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->button()
                    ->visible(fn ($record) =>
                        $record->status === 'in_progress' && abs((float) $record->difference) < 0.01
                    )
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->forceFill([
                            'status'        => 'reconciled',
                            'reviewed_by'   => auth()->id(),
                            'reconciled_at' => now(),
                        ])->save();
                        Notification::make()->success()->title('Reconciliation completed.')->send();
                    }),

                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\Action::make('print_summary')
                        ->label('Print Summary')
                        ->icon('heroicon-o-document-text')
                        ->color('gray')
                        ->url(fn ($record) => route('finance.bank-reconciliation.summary', $record))
                        ->openUrlInNewTab(),

                    \Filament\Actions\Action::make('print_detail')
                        ->label('Print Detail')
                        ->icon('heroicon-o-document-magnifying-glass')
                        ->color('gray')
                        ->url(fn ($record) => route('finance.bank-reconciliation.detail', $record))
                        ->openUrlInNewTab(),
                ])
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->button(),
            ])
            ->defaultSort('statement_date', 'desc')
            ->striped()
            ->paginated(true);
    }

    // ── Livewire hooks — recompute GL preview when account or date changes ────

    public function updatedBankAccountId(): void
    {
        $this->recomputeGlBalance();
    }

    public function updatedEndingDate(): void
    {
        $this->recomputeGlBalance();
    }

    /**
     * Reads the current book balance from the GeneralLedger table so the user
     * can see it on the wizard before they click "Start Reconciling".
     */
    protected function recomputeGlBalance(): void
    {
        if ($this->bank_account_id && $this->ending_date) {
            $this->computed_gl_balance = BankReconciliation::glBalanceFor(
                (int) $this->bank_account_id,
                $this->ending_date
            );
        } else {
            $this->computed_gl_balance = null;
        }
    }

    // ── Start Reconciling action ───────────────────────────────────────

    public function startReconciling(): void
    {
        $this->validate([
            'bank_account_id' => 'required|exists:finance_bank_accounts,id',
            'ending_balance'  => 'required|numeric',
            'ending_date'     => 'required|date',
        ]);

        // If an in-progress reconciliation already exists, resume it
        $existing = BankReconciliation::where('bank_account_id', $this->bank_account_id)
            ->where('status', 'in_progress')
            ->latest('statement_date')
            ->first();

        if ($existing) {
            Notification::make()
                ->info()
                ->title('Resuming existing reconciliation')
                ->body("{$existing->reference} was already in progress.")
                ->send();

            $this->redirect(
                route('filament.admin.resources.finance.bank.reconciliations.edit', $existing)
            );
            return;
        }

        // Generate reference — include soft-deleted rows so we never re-issue a ref
        $year = now()->year;
        $last = BankReconciliation::withTrashed()->where('reference', 'like', "BR-{$year}-%")
            ->orderByRaw('LENGTH(reference) DESC')
            ->orderBy('reference', 'desc')
            ->value('reference');
        $seq       = $last ? ((int) last(explode('-', $last))) + 1 : 1;
        $reference = sprintf('BR-%d-%04d', $year, $seq);

        // Match to an accounting period
        $periodId = $this->resolveAccountingPeriodId($this->ending_date);
        if (! $periodId) {
            Notification::make()
                ->danger()
                ->title('No accounting period found')
                ->body('Create or open an accounting period that covers the selected statement date, then try again.')
                ->send();
            return;
        }

        $statementBalance = (float) ($this->ending_balance ?? 0);

        // ✔ Core fix: compute the true GL ending balance from the ledger.
        // Reads running_balance from the GeneralLedger table for the bank
        // account's linked chart-of-account as of the statement date.
        $glBalance = BankReconciliation::glBalanceFor(
            (int) $this->bank_account_id,
            $this->ending_date
        );

        // With no outstanding items yet, adjustedBank = statementBalance
        $difference = $statementBalance - $glBalance;

        $reconciliation = BankReconciliation::create([
            'reference'             => $reference,
            'bank_account_id'       => $this->bank_account_id,
            'accounting_period_id'  => $periodId,
            'statement_date'        => $this->ending_date,
            'statement_balance'     => $statementBalance,
            'gl_balance'            => $glBalance,
            'outstanding_deposits'  => 0,
            'outstanding_cheques'   => 0,
            'adjusted_bank_balance' => $statementBalance,
            'difference'            => $difference,
            'status'                => 'in_progress',
            'prepared_by'           => auth()->id(),
        ]);

        Notification::make()
            ->success()
            ->title('Reconciliation started — ' . $reference)
            ->body('Add any outstanding deposits and unpresented cheques, then mark items as cleared.')
            ->send();

        $this->redirect(
            route('filament.admin.resources.finance.bank.reconciliations.edit', $reconciliation)
        );
    }

    // ── View data ─────────────────────────────────────────────────────

    protected function getViewData(): array
    {
        $bankAccounts = BankAccount::activeOptions();

        $lastReconciliation = $this->bank_account_id
            ? BankReconciliation::where('bank_account_id', $this->bank_account_id)
                ->where('status', 'reconciled')
                ->orderByDesc('statement_date')
                ->first()
            : null;

        $inProgressReconciliation = $this->bank_account_id
            ? BankReconciliation::where('bank_account_id', $this->bank_account_id)
                ->where('status', 'in_progress')
                ->latest('statement_date')
                ->first()
            : null;

        return compact('bankAccounts', 'lastReconciliation', 'inProgressReconciliation');
    }

    protected function resolveAccountingPeriodId(string $statementDate): ?int
    {
        // Primary lookup: period that contains statement date.
        $directMatch = AccountingPeriod::query()
            ->whereDate('start_date', '<=', $statementDate)
            ->whereDate('end_date', '>=', $statementDate)
            ->value('id');

        if ($directMatch) {
            return (int) $directMatch;
        }

        // Fallback 1: nearest earlier period, useful when historical statements
        // are entered but exact period ranges were not fully maintained.
        $nearestPast = AccountingPeriod::query()
            ->whereDate('end_date', '<=', $statementDate)
            ->orderByDesc('end_date')
            ->value('id');

        if ($nearestPast) {
            return (int) $nearestPast;
        }

        // Fallback 2: earliest future period as a last-resort mapping.
        $nearestFuture = AccountingPeriod::query()
            ->whereDate('start_date', '>=', $statementDate)
            ->orderBy('start_date')
            ->value('id');

        return $nearestFuture ? (int) $nearestFuture : null;
    }
}
